<?php

namespace App\Imports;

use App\Models\Alumni;
use App\Models\City;
use App\Models\Province;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\AlumniProvisioningService;
use App\Support\ImportScopeGuard;
use App\Support\TracerFieldCodes;
use App\Support\TracerValueParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\RemembersChunkOffset;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk-updates tracer_responses matched by NIM (nimnomor_mhs). If the NIM
 * isn't registered yet, the alumni is created from the identity columns in
 * the same row — but since this format only carries a prodi CODE (not its
 * name/level/faculty), the prodi must already exist as master data; a code
 * that doesn't resolve to an existing StudyProgram is skipped rather than
 * guessed at.
 *
 * Note: this never creates a User login account (no real tanggal_lahir is
 * available from this format) — alumni created this way can't log in via
 * NIM+DOB until a real academic-system integration or an admin sets one.
 *
 * Column set otherwise mirrors TracerResponsesExport exactly (the official
 * "Pelaporan Tracer Study" national reporting template), so an exported
 * file can be edited and re-uploaded as-is.
 *
 * Performance: alumni/study-program/province/city lookups are preloaded
 * once per chunk (provinces/cities are small tables, loaded in full; alumni/
 * prodi are loaded only for the codes present in that chunk) — without this,
 * a several-thousand-row file turns into tens of thousands of individual
 * queries.
 *
 * Reads the file in chunks (see WithChunkReading below) instead of loading
 * it whole: a large real-world export made PhpSpreadsheet exceed PHP's
 * memory_limit trying to hold the entire file in memory at once. Chunking
 * keeps peak memory bounded regardless of file size, at the cost of
 * re-running the per-chunk preload queries above once per chunk instead of
 * once for the whole file — still far cheaper than one query per row.
 *
 * Each row's writes run inside their own transaction, not one transaction
 * for the whole file: on Postgres a single failed row (e.g. a NOT NULL or
 * unique-constraint violation) poisons the entire transaction and rolls
 * back every other row too. A bad row is skipped and reported instead of
 * failing the whole import.
 */
class TracerResponsesImport implements SkipsEmptyRows, ToCollection, WithChunkReading, WithHeadingRow
{
    use RemembersChunkOffset;

    public int $created = 0;

    public int $updated = 0;

    /** @var list<array{row: int, reason: string}> */
    public array $skipped = [];

    public function __construct(private readonly User $importedBy) {}

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        $nims = $rows->map(fn (Collection $row) => TracerValueParser::str($row['nimnomor_mhs'] ?? null))->filter()->unique()->values();
        $prodiCodes = $rows->map(fn (Collection $row) => TracerValueParser::str($row['kode_prodi'] ?? null))->filter()->unique()->values();

        $alumniByNim = Alumni::whereIn('nim', $nims)->with('tracerResponse')->get()->keyBy('nim');
        $studyProgramsByCode = StudyProgram::whereIn('code', $prodiCodes)->with('faculty')->get()->keyBy('code');
        $provincesByCode = Province::all()->keyBy('code');
        $citiesByCode = City::all()->keyBy('code');

        foreach ($rows as $index => $row) {
            // getChunkOffset() is the file row number this chunk starts at (already
            // accounts for the heading row); null only outside chunked reading (e.g. tests
            // that call collection() directly), where row 2 is the first data row.
            $line = ($this->getChunkOffset() ?? 2) + $index;

            try {
                DB::transaction(fn () => $this->importRow(
                    $row, $line, $alumniByNim, $studyProgramsByCode, $provincesByCode, $citiesByCode,
                ));
            } catch (\Throwable $e) {
                $this->skipped[] = ['row' => $line, 'reason' => 'Gagal disimpan: '.$e->getMessage()];
            }
        }
    }

    private function importRow(
        Collection $row,
        int $line,
        Collection $alumniByNim,
        Collection $studyProgramsByCode,
        Collection $provincesByCode,
        Collection $citiesByCode,
    ): void {
        $nim = TracerValueParser::str($row['nimnomor_mhs'] ?? null);

        if ($nim === null) {
            $this->skipped[] = ['row' => $line, 'reason' => 'Kolom NIM/Nomor Mhs kosong'];

            return;
        }

        $alumni = $alumniByNim->get($nim);
        $isNew = $alumni === null;

        if ($alumni) {
            if (! $this->importedBy->can('fillTracer', $alumni)) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} di luar cakupan Anda"];

                return;
            }
        } else {
            $prodiCode = TracerValueParser::str($row['kode_prodi'] ?? null);
            $studyProgram = $prodiCode !== null ? $studyProgramsByCode->get($prodiCode) : null;

            if (! ImportScopeGuard::allows($this->importedBy, $studyProgram?->faculty?->code, $prodiCode)) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} (baru) di luar cakupan Anda"];

                return;
            }

            $alumni = $this->findOrCreateAlumni($nim, $row, $studyProgram);

            if (! $alumni) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim}: kode prodi tidak ditemukan"];

                return;
            }

            $alumniByNim->put($nim, $alumni);
        }

        $data = $this->mapRow($row, $provincesByCode, $citiesByCode);
        $data['submitted_by_user_id'] = $this->importedBy->id;
        $data['submitted_at'] = now();

        if ($alumni->tracerResponse) {
            $alumni->tracerResponse->update($data);
        } else {
            $alumni->setRelation('tracerResponse', $alumni->tracerResponse()->create($data));
        }

        $isNew ? $this->created++ : $this->updated++;
    }

    /**
     * Creating a brand-new alumni from this format only has a prodi CODE to
     * go on (no name/level/faculty columns), so the prodi must already
     * exist as master data — a code that doesn't resolve to an existing
     * StudyProgram can't be bootstrapped and is skipped instead of guessed.
     */
    private function findOrCreateAlumni(string $nim, Collection $row, ?StudyProgram $studyProgram): ?Alumni
    {
        if ($studyProgram === null || $studyProgram->faculty === null) {
            return null;
        }

        return (new AlumniProvisioningService)->findOrCreate($nim, [
            'nama' => TracerValueParser::str($row['nama_mhs'] ?? null),
            'email' => TracerValueParser::str($row['email_mhs'] ?? null),
            'faculty_code' => $studyProgram->faculty->code,
            'faculty_name' => $studyProgram->faculty->name,
            'prodi_code' => $studyProgram->code,
            'prodi_name' => $studyProgram->name,
            'prodi_level' => $studyProgram->level,
            'graduation_year' => TracerValueParser::int($row['tahun_lulus_keluar'] ?? null),
            'nik' => TracerValueParser::str($row['nik'] ?? null),
            'npwp' => TracerValueParser::str($row['npwp'] ?? null),
            'phone' => TracerValueParser::str($row['nomor_hp_mhs'] ?? null),
        ]);
    }

    private function mapRow(Collection $row, Collection $provincesByCode, Collection $citiesByCode): array
    {
        $workProvinceId = $provincesByCode->get(TracerValueParser::str($row['f5a1'] ?? null))?->id;
        $workCityId = $citiesByCode->get(TracerValueParser::str($row['f5a2'] ?? null))?->id;

        $data = ['work_province_id' => $workProvinceId, 'work_city_id' => $workCityId];

        foreach (TracerFieldCodes::codes() as $code) {
            $data[$code] = $this->castValue($code, $row[$code] ?? null);
        }

        return $data;
    }

    private function castValue(string $code, mixed $value): mixed
    {
        return match (true) {
            in_array($code, TracerFieldCodes::decimalCodes(), true) => TracerValueParser::decimal($value),
            in_array($code, TracerFieldCodes::integerCodes(), true) => TracerValueParser::int($value),
            in_array($code, TracerFieldCodes::booleanCodes(), true) => TracerValueParser::bool($value),
            default => TracerValueParser::str($value),
        };
    }
}
