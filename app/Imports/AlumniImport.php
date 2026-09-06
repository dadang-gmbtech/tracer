<?php

namespace App\Imports;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\FacultyCodeGuesser;
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
 * created on the fly. The faculty for a new program studi is resolved, in
 * order: the file's own kode_fakultas/nama_fakultas, the faculty picked on
 * the import form ($defaultFaculty), or — as a last resort — guessed from
 * the NIM's first letter (see FacultyCodeGuesser), which is handy for the
 * real export format that has no faculty columns at all.
 *
 * If tgllahir/tanggal_lahir is filled in, a login account (NIM + Tanggal
 * Lahir) is also created/updated for that alumni; leave it blank to skip
 * login provisioning (e.g. when the real birth date isn't known yet).
 *
 * Performance: every lookup (alumni/user by NIM or email, program studi by
 * code, faculty by code) is preloaded once into an in-memory map before the
 * loop — a naive per-row query approach turns a few-thousand-row file into
 * tens of thousands of round trips and can take minutes.
 *
 * Each row's writes run inside their own transaction, not one transaction
 * for the whole file: on Postgres, a single failed row (e.g. two alumni
 * sharing a personal email, tripping users.email's unique constraint)
 * poisons the entire transaction and rolls back every other row too. A bad
 * row is now skipped and reported instead of failing the whole import.
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
        $usersByEmail = User::all()->keyBy('email');
        $studyProgramsByCode = StudyProgram::whereIn('code', $prodiCodes)->with('faculty')->get()->keyBy('code');
        $facultiesByCode = Faculty::all()->keyBy('code');

        foreach ($rows as $index => $row) {
            $line = $index + 2; // 0-based collection index + 1 for the heading row

            try {
                DB::transaction(fn () => $this->importRow($row, $line, $alumniByNim, $usersByNim, $usersByEmail, $studyProgramsByCode, $facultiesByCode));
            } catch (\Throwable $e) {
                $this->skipped[] = ['row' => $line, 'reason' => 'Gagal disimpan: '.$e->getMessage()];
            }
        }
    }

    private function importRow(
        Collection $row,
        int $line,
        Collection $alumniByNim,
        Collection $usersByNim,
        Collection $usersByEmail,
        Collection $studyProgramsByCode,
        Collection $facultiesByCode,
    ): void {
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
        $facultyCode = $this->pick($row, ['kode_fakultas', 'kodefak'])
            ?? $this->defaultFaculty?->code
            ?? FacultyCodeGuesser::guess($nim, $facultiesByCode);
        $facultyName = $this->pick($row, ['nama_fakultas', 'namafakultas']) ?? $this->defaultFaculty?->name;

        if (! $studyProgram && $facultyCode === null) {
            $this->skipped[] = [
                'row' => $line,
                'reason' => "NIM {$nim}: kode prodi {$prodiCode} belum terdaftar dan fakultas tidak bisa ditebak dari NIM — tambahkan prodinya dulu lewat Administrasi > Program Studi, sertakan kolom kode_fakultas, atau pilih Fakultas di form impor",
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

        $this->provisionLoginIfDobProvided($alumni, $email, $this->pick($row, ['tgllahir', 'tanggal_lahir']), $usersByNim, $usersByEmail, $line);
    }

    /**
     * Skips login provisioning (but keeps the alumni data saved above) when
     * the target email is already taken by a different NIM's account —
     * common when two alumni share a personal email, and would otherwise
     * trip users.email's unique constraint and roll back the whole row.
     */
    private function provisionLoginIfDobProvided(Alumni $alumni, ?string $email, ?string $tanggalLahir, Collection $usersByNim, Collection $usersByEmail, int $line): void
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

            return;
        }

        $loginEmail = $email ?? $alumni->email ?? ($alumni->nim.'@mhs.unsoed.ac.id');
        $emailOwner = $usersByEmail->get($loginEmail);

        // Same person often reappears under a new NIM (e.g. continuing from
        // S1 to S2/S3) with the same personal email. Login is by NIM +
        // Tanggal Lahir, not by email, so it's safe to fall back to a
        // NIM-based email here rather than skip creating this account.
        if ($emailOwner && $emailOwner->nim !== $alumni->nim) {
            $fallbackEmail = $alumni->nim.'@mhs.unsoed.ac.id';

            if ($usersByEmail->has($fallbackEmail)) {
                $this->skipped[] = [
                    'row' => $line,
                    'reason' => "NIM {$alumni->nim}: data alumni tersimpan, tapi akun login tidak dibuat — email {$loginEmail} dan {$fallbackEmail} sudah dipakai",
                ];

                return;
            }

            $this->skipped[] = [
                'row' => $line,
                'reason' => "NIM {$alumni->nim}: akun login dibuat dengan email {$fallbackEmail} (bukan {$loginEmail}), karena email itu sudah dipakai NIM {$emailOwner->nim} — kemungkinan alumni yang sama melanjutkan studi",
            ];
            $loginEmail = $fallbackEmail;
        }

        $user = User::create([
            'nim' => $alumni->nim,
            'name' => $alumni->nama,
            'email' => $loginEmail,
            'password' => bcrypt(Str::random(32)),
            'tanggal_lahir' => $dob,
            'no_telp' => $alumni->phone,
            'status' => 'active',
        ]);
        $usersByNim->put($alumni->nim, $user);
        $usersByEmail->put($loginEmail, $user);

        $user->assignRole('Alumni');
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
