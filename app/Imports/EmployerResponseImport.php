<?php

namespace App\Imports;

use App\Models\Alumni;
use App\Models\EmployerResponse;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\AlumniProvisioningService;
use App\Support\FacultyCodeGuesser;
use App\Support\ImportScopeGuard;
use App\Support\TracerValueParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk-adds "Pengguna Alumni" (employer) feedback collected offline — a
 * paper form, WhatsApp, or a phone call — matched to an existing alumni by
 * NIM. Also accepts the real combined export format (national tracer
 * columns joined with an employer-survey addendum: kodefak, namafakultas,
 * kerjasama, pengembangandiri, ... with ratings as text — Sangat
 * Baik/Baik/Cukup/Kurang — instead of 1-4), which carries full alumni
 * identity too, so an unregistered NIM is bootstrapped the same way
 * TracerResponsesImport does rather than being skipped outright.
 *
 * A row with no employer columns filled in at all (common in that combined
 * format — not every alumni has been rated yet) is skipped silently, not
 * reported as an error; only a row that has *some* employer data but is
 * missing or invalid elsewhere is reported. More than one response per
 * alumni is allowed, same as the public form — each row always adds a new
 * response rather than updating a previous one.
 *
 * Each row's write runs in its own transaction — see TracerResponsesImport
 * for why (a single failed row must not roll back the rest of the file).
 */
class EmployerResponseImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    /**
     * @var array<string, list<string>>
     */
    private const RATING_FIELD_ALIASES = [
        'q1_kerja_sama_tim' => ['q1_kerja_sama_tim', 'kerjasama'],
        'q2_pengembangan_diri' => ['q2_pengembangan_diri', 'pengembangandiri'],
        'q3_komunikasi' => ['q3_komunikasi', 'komunikasi'],
        'q4_teknologi_informasi' => ['q4_teknologi_informasi', 'penggunaanteknologi'],
        'q5_bahasa_asing' => ['q5_bahasa_asing', 'bahasaasing'],
        'q6_keahlian' => ['q6_keahlian', 'kualitaskeahlian'],
        'q7_integritas' => ['q7_integritas', 'integritas'],
    ];

    public int $created = 0;

    /**
     * Rows with no employer data at all — not an error, just nothing to import.
     */
    public int $noFeedback = 0;

    /** @var list<array{row: int, reason: string}> */
    public array $skipped = [];

    public function __construct(private readonly User $importedBy) {}

    public function collection(Collection $rows): void
    {
        $nims = $rows->map(fn (Collection $row) => $this->pick($row, ['nim']))->filter()->unique()->values();
        $prodiCodes = $rows->map(fn (Collection $row) => $this->pick($row, ['kodeprog', 'kode_prodi']))->filter()->unique()->values();

        $alumniByNim = Alumni::whereIn('nim', $nims)->get()->keyBy('nim');
        $studyProgramsByCode = StudyProgram::whereIn('code', $prodiCodes)->with('faculty')->get()->keyBy('code');
        $facultiesByCode = Faculty::all()->keyBy('code');

        foreach ($rows as $index => $row) {
            $line = $index + 2; // 0-based collection index + 1 for the heading row

            try {
                DB::transaction(fn () => $this->importRow($row, $line, $alumniByNim, $studyProgramsByCode, $facultiesByCode));
            } catch (\Throwable $e) {
                $this->skipped[] = ['row' => $line, 'reason' => 'Gagal disimpan: '.$e->getMessage()];
            }
        }
    }

    private function importRow(Collection $row, int $line, Collection $alumniByNim, Collection $studyProgramsByCode, Collection $facultiesByCode): void
    {
        $nim = $this->pick($row, ['nim']);

        if ($nim === null) {
            $this->skipped[] = ['row' => $line, 'reason' => 'Kolom nim kosong'];

            return;
        }

        $namaPengisi = $this->pick($row, ['nama_pengisi', 'namalengkap']);
        $namaPerusahaan = $this->pick($row, ['nama_perusahaan', 'namaperusahaan']);
        $ratingInputs = array_map(fn (array $aliases) => $this->pick($row, $aliases), self::RATING_FIELD_ALIASES);

        $hasEmployerData = $namaPengisi !== null || $namaPerusahaan !== null
            || collect($ratingInputs)->filter(fn ($v) => $v !== null)->isNotEmpty();

        if (! $hasEmployerData) {
            $this->noFeedback++;

            return;
        }

        $alumni = $alumniByNim->get($nim);

        if ($alumni) {
            if (! $this->importedBy->can('fillTracer', $alumni)) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} di luar cakupan Anda"];

                return;
            }
        } else {
            if (! $this->authorizedForRow($row, $nim, $facultiesByCode)) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} (baru) di luar cakupan Anda"];

                return;
            }

            $alumni = $this->findOrCreateAlumni($nim, $row, $studyProgramsByCode, $facultiesByCode);

            if (! $alumni) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim}: alumni belum terdaftar dan data fakultas/prodi tidak lengkap untuk membuat baru"];

                return;
            }

            $alumniByNim->put($nim, $alumni);
        }

        $ratings = [];

        foreach ($ratingInputs as $field => $raw) {
            $value = $this->parseRating($raw);

            if ($value === null) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim}: kolom penilaian ({$field}) harus diisi 1-4 atau Sangat Baik/Baik/Cukup/Kurang"];

                return;
            }

            $ratings[$field] = $value;
        }

        if ($namaPengisi === null || $namaPerusahaan === null) {
            $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim}: nama pengisi dan nama perusahaan wajib diisi"];

            return;
        }

        EmployerResponse::create([
            'alumni_id' => $alumni->id,
            'nama_pengisi' => $namaPengisi,
            'jabatan' => $this->pick($row, ['jabatan']),
            'nama_perusahaan' => $namaPerusahaan,
            'alamat_perusahaan' => $this->pick($row, ['alamat_perusahaan', 'alamatperusahaan']),
            'no_telp' => $this->pick($row, ['no_telp', 'telpperusahaan']),
            ...$ratings,
        ]);

        $this->created++;
    }

    private function authorizedForRow(Collection $row, string $nim, Collection $facultiesByCode): bool
    {
        return ImportScopeGuard::allows(
            $this->importedBy,
            FacultyCodeGuesser::normalize($this->pick($row, ['kodefak', 'kode_fakultas'])) ?? FacultyCodeGuesser::guess($nim, $facultiesByCode),
            $this->pick($row, ['kodeprog', 'kode_prodi']),
        );
    }

    private function findOrCreateAlumni(string $nim, Collection $row, Collection $studyProgramsByCode, Collection $facultiesByCode): ?Alumni
    {
        $facultyCode = FacultyCodeGuesser::normalize($this->pick($row, ['kodefak', 'kode_fakultas'])) ?? FacultyCodeGuesser::guess($nim, $facultiesByCode);
        $prodiCode = $this->pick($row, ['kodeprog', 'kode_prodi']);

        if ($facultyCode === null || $prodiCode === null) {
            return null;
        }

        $alumni = (new AlumniProvisioningService)->findOrCreate($nim, [
            'nama' => $this->pick($row, ['nama', 'nmmhsmsmh']),
            'email' => $this->pick($row, ['emailpersonal', 'emailunsoed', 'email']),
            'faculty_code' => $facultyCode,
            'faculty_name' => $this->pick($row, ['namafakultas', 'nama_fakultas']),
            'prodi_code' => $prodiCode,
            'prodi_name' => $this->pick($row, ['namaprogdikti', 'nama_prodi']),
            'prodi_level' => $this->pick($row, ['namajenjang', 'jenjang']),
            'graduation_year' => TracerValueParser::int($this->pick($row, ['tahunlulus', 'tahun_lulus'])),
            'phone' => $this->pick($row, ['notelp', 'no_telp']),
        ]);

        if (! $studyProgramsByCode->has($prodiCode)) {
            $studyProgramsByCode->put($prodiCode, $alumni->studyProgram);
        }

        if (! $facultiesByCode->has($facultyCode)) {
            $facultiesByCode->put($facultyCode, $alumni->faculty);
        }

        return $alumni;
    }

    private function parseRating(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        $numeric = TracerValueParser::int($raw);

        if ($numeric !== null && $numeric >= 1 && $numeric <= 4) {
            return $numeric;
        }

        return match (strtolower(trim($raw))) {
            'sangat baik' => 1,
            'baik' => 2,
            'cukup' => 3,
            'kurang' => 4,
            default => null,
        };
    }

    /**
     * Try each candidate header name in order and return the first
     * non-empty value — see AlumniImport::pick() for why (real exports and
     * hand-made templates don't agree on exact column spelling).
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
