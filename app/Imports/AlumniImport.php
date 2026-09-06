<?php

namespace App\Imports;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\ImportScopeGuard;
use App\Support\TracerValueParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk create/update the alumni roster itself (identity only — no tracer
 * answers). Matches the columns of the real SIAKAD-style export (nim, nama,
 * tahunlulu, emailunsoed, emailpersonal, npwp, tgllahir, notelp, kodeprog,
 * namajenjang, namaprogdikti, plus extra columns like kodetahunakad/noskpi
 * which are accepted but ignored) — several alternate header spellings are
 * also accepted (see pick()) so a template-based file works too.
 *
 * The program studi (kodeprog) must already be registered (via Admin >
 * Program Studi, or a prior Impor Data Tracer upload) UNLESS the file also
 * carries kode_fakultas/nama_fakultas, in which case a new one is created.
 *
 * If tgllahir/tanggal_lahir is filled in, a login account (NIM + Tanggal
 * Lahir) is also created/updated for that alumni; leave it blank to skip
 * login provisioning (e.g. when the real birth date isn't known yet).
 */
class AlumniImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    /** @var list<array{row: int, reason: string}> */
    public array $skipped = [];

    public function __construct(private readonly User $importedBy) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2; // 0-based collection index + 1 for the heading row
            $nim = $this->pick($row, ['nim']);

            if ($nim === null) {
                $this->skipped[] = ['row' => $line, 'reason' => 'Kolom nim kosong'];

                continue;
            }

            $prodiCode = $this->pick($row, ['kodeprog', 'kode_prodi']);

            if ($prodiCode === null) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim}: kolom kodeprog kosong"];

                continue;
            }

            $studyProgram = StudyProgram::where('code', $prodiCode)->first();
            $facultyCode = $this->pick($row, ['kode_fakultas', 'kodefak']);

            if (! $studyProgram && $facultyCode === null) {
                $this->skipped[] = [
                    'row' => $line,
                    'reason' => "NIM {$nim}: kode prodi {$prodiCode} belum terdaftar — tambahkan dulu lewat Administrasi > Program Studi, atau sertakan kolom kode_fakultas",
                ];

                continue;
            }

            $resolvedFacultyCode = $studyProgram ? $studyProgram->faculty->code : $facultyCode;
            $existing = Alumni::where('nim', $nim)->first();

            $authorized = $existing
                ? $this->importedBy->can('fillTracer', $existing)
                : ImportScopeGuard::allows($this->importedBy, $resolvedFacultyCode, $prodiCode);

            if (! $authorized) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} di luar cakupan Anda"];

                continue;
            }

            if (! $studyProgram) {
                $faculty = Faculty::firstOrCreate(
                    ['code' => $facultyCode],
                    ['name' => $this->pick($row, ['nama_fakultas', 'namafakultas']) ?? $facultyCode]
                );

                $studyProgram = StudyProgram::firstOrCreate(
                    ['code' => $prodiCode],
                    [
                        'faculty_id' => $faculty->id,
                        'name' => $this->pick($row, ['nama_prodi', 'namaprogdikti']) ?? $prodiCode,
                        'level' => $this->pick($row, ['jenjang', 'namajenjang']) ?? '-',
                    ]
                );
            }

            $email = $this->pick($row, ['emailpersonal', 'emailunsoed', 'email']);

            $alumni = Alumni::updateOrCreate(
                ['nim' => $nim],
                [
                    'nama' => $this->pick($row, ['nama']) ?? $nim,
                    'email' => $email,
                    'faculty_id' => $studyProgram->faculty_id,
                    'program_study_id' => $studyProgram->id,
                    'graduation_year' => TracerValueParser::int($this->pick($row, ['tahunlulu', 'tahunlulus', 'tahun_lulus'])),
                    'nik' => $this->pick($row, ['nik']),
                    'npwp' => $this->pick($row, ['npwp']),
                    'phone' => $this->pick($row, ['notelp', 'no_telp']),
                ]
            );

            $existing ? $this->updated++ : $this->created++;

            $this->provisionLoginIfDobProvided($alumni, $email, $this->pick($row, ['tgllahir', 'tanggal_lahir']));
        }
    }

    private function provisionLoginIfDobProvided(Alumni $alumni, ?string $email, ?string $tanggalLahir): void
    {
        $dob = TracerValueParser::date($tanggalLahir);

        if ($dob === null) {
            return;
        }

        $user = User::where('nim', $alumni->nim)->first();

        if ($user) {
            $user->update([
                'name' => $alumni->nama,
                'tanggal_lahir' => $dob,
                'no_telp' => $alumni->phone,
                'status' => 'active',
            ]);
        } else {
            $user = User::create([
                'nim' => $alumni->nim,
                'name' => $alumni->nama,
                'email' => $email ?? $alumni->email ?? ($alumni->nim.'@mhs.unsoed.ac.id'),
                'password' => bcrypt(Str::random(32)),
                'tanggal_lahir' => $dob,
                'no_telp' => $alumni->phone,
                'status' => 'active',
            ]);
        }

        if (! $user->hasRole('Alumni')) {
            $user->assignRole('Alumni');
        }
    }

    /**
     * Try each candidate header name in order and return the first
     * non-empty value — real exports and hand-made templates don't always
     * agree on exact column spelling (emailunsoed vs email, tgllahir vs
     * tanggal_lahir, kodefak vs kode_fakultas, etc.).
     *
     * @param  list<string>  $keys
     */
    private function pick(Collection $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = TracerValueParser::str($row[$key] ?? null);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }
}
