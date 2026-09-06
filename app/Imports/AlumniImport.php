<?php

namespace App\Imports;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\ImportScopeGuard;
use App\Support\TracerValueParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk create/update the alumni roster itself (identity only — no tracer
 * answers). Matches the columns of the real SIAKAD-style export (nim, nama,
 * tahunlulus, emailunsoed, emailpersonal, npwp, tgllahir, notelp, kodeprog,
 * namajenjang, namaprogdikti, plus extra columns like kodetahunakad/noskpi
 * which are accepted but ignored) — several alternate header spellings are
 * also accepted (see pick()) so a template-based file works too.
 *
 * The program studi (kodeprog) must already be registered (via Admin >
 * Program Studi, or a prior Impor Data Tracer upload) unless it can be
 * created on the fly — either the file carries kode_fakultas/nama_fakultas,
 * or the uploader picked a faculty on the import form ($defaultFaculty),
 * which is handy for the real export format that has no faculty columns at
 * all: upload the file once per faculty and pick it from the dropdown.
 *
 * If tgllahir/tanggal_lahir is filled in, a login account (NIM + Tanggal
 * Lahir) is also created/updated for that alumni; leave it blank to skip
 * login provisioning (e.g. when the real birth date isn't known yet).
 *
 * Performance: every lookup (alumni/user by NIM, program studi by code,
 * faculty by code) is preloaded once into an in-memory map before the loop,
 * and all writes run inside one transaction — a naive per-row query/query
 * approach turns a few-thousand-row file into tens of thousands of
 * round trips and can take minutes; this keeps it to a handful of SELECTs
 * plus one write per row.
 */
class AlumniImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    /** @var list<array{row: int, reason: string}> */
    public array $skipped = [];

    public function __construct(
        private readonly User $importedBy,
        private readonly ?Faculty $defaultFaculty = null,
    ) {}

    public function collection(Collection $rows): void
    {
        $nims = $rows->map(fn (Collection $row) => $this->pick($row, ['nim']))->filter()->unique()->values();
        $prodiCodes = $rows->map(fn (Collection $row) => $this->pick($row, ['kodeprog', 'kode_prodi']))->filter()->unique()->values();

        $alumniByNim = Alumni::whereIn('nim', $nims)->get()->keyBy('nim');
        $usersByNim = User::whereIn('nim', $nims)->get()->keyBy('nim');
        $studyProgramsByCode = StudyProgram::whereIn('code', $prodiCodes)->with('faculty')->get()->keyBy('code');
        $facultiesByCode = Faculty::all()->keyBy('code');

        DB::transaction(function () use ($rows, $alumniByNim, $usersByNim, $studyProgramsByCode, $facultiesByCode) {
            foreach ($rows as $index => $row) {
                $this->importRow($row, $index, $alumniByNim, $usersByNim, $studyProgramsByCode, $facultiesByCode);
            }
        });
    }

    private function importRow(
        Collection $row,
        int $index,
        Collection $alumniByNim,
        Collection $usersByNim,
        Collection $studyProgramsByCode,
        Collection $facultiesByCode,
    ): void {
        $line = $index + 2; // 0-based collection index + 1 for the heading row
        $nim = $this->pick($row, ['nim']);

        if ($nim === null) {
            $this->skipped[] = ['row' => $line, 'reason' => 'Kolom nim kosong'];

            return;
        }

        $prodiCode = $this->pick($row, ['kodeprog', 'kode_prodi']);

        if ($prodiCode === null) {
            $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim}: kolom kodeprog kosong"];

            return;
        }

        $studyProgram = $studyProgramsByCode->get($prodiCode);
        $facultyCode = $this->pick($row, ['kode_fakultas', 'kodefak']) ?? $this->defaultFaculty?->code;
        $facultyName = $this->pick($row, ['nama_fakultas', 'namafakultas']) ?? $this->defaultFaculty?->name;

        if (! $studyProgram && $facultyCode === null) {
            $this->skipped[] = [
                'row' => $line,
                'reason' => "NIM {$nim}: kode prodi {$prodiCode} belum terdaftar — tambahkan dulu lewat Administrasi > Program Studi, sertakan kolom kode_fakultas, atau pilih Fakultas di form impor",
            ];

            return;
        }

        $resolvedFacultyCode = $studyProgram ? $studyProgram->faculty->code : $facultyCode;
        $existing = $alumniByNim->get($nim);

        $authorized = $existing
            ? $this->importedBy->can('fillTracer', $existing)
            : ImportScopeGuard::allows($this->importedBy, $resolvedFacultyCode, $prodiCode);

        if (! $authorized) {
            $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} di luar cakupan Anda"];

            return;
        }

        if (! $studyProgram) {
            $faculty = $facultiesByCode->get($facultyCode);

            if (! $faculty) {
                $faculty = Faculty::create(['code' => $facultyCode, 'name' => $facultyName ?? $facultyCode]);
                $facultiesByCode->put($facultyCode, $faculty);
            }

            $studyProgram = StudyProgram::create([
                'code' => $prodiCode,
                'faculty_id' => $faculty->id,
                'name' => $this->pick($row, ['nama_prodi', 'namaprogdikti']) ?? $prodiCode,
                'level' => $this->pick($row, ['jenjang', 'namajenjang']) ?? '-',
            ]);
            $studyProgram->setRelation('faculty', $faculty);
            $studyProgramsByCode->put($prodiCode, $studyProgram);
        }

        $email = $this->pick($row, ['emailpersonal', 'emailunsoed', 'email']);

        $attributes = [
            'nama' => $this->pick($row, ['nama']) ?? $nim,
            'email' => $email,
            'faculty_id' => $studyProgram->faculty_id,
            'program_study_id' => $studyProgram->id,
            'graduation_year' => TracerValueParser::int($this->pick($row, ['tahunlulus', 'tahunlulu', 'tahun_lulus'])),
            'nik' => $this->pick($row, ['nik']),
            'npwp' => $this->pick($row, ['npwp']),
            'phone' => $this->pick($row, ['notelp', 'no_telp']),
        ];

        if ($existing) {
            $existing->update($attributes);
            $alumni = $existing;
            $this->updated++;
        } else {
            $alumni = Alumni::create(['nim' => $nim, ...$attributes]);
            $alumniByNim->put($nim, $alumni);
            $this->created++;
        }

        $this->provisionLoginIfDobProvided($alumni, $email, $this->pick($row, ['tgllahir', 'tanggal_lahir']), $usersByNim);
    }

    private function provisionLoginIfDobProvided(Alumni $alumni, ?string $email, ?string $tanggalLahir, Collection $usersByNim): void
    {
        $dob = TracerValueParser::date($tanggalLahir);

        if ($dob === null) {
            return;
        }

        $user = $usersByNim->get($alumni->nim);

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
            $usersByNim->put($alumni->nim, $user);
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
