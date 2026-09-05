<?php

namespace App\Imports;

use App\Models\Alumni;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use App\Support\TracerFieldCodes;
use App\Support\TracerValueParser;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk-updates tracer_responses for alumni that already exist in the system,
 * matched by NIM (nimhsmsmh). Column set mirrors TracerResponsesExport
 * exactly (minus f504), so an exported file can be edited and re-uploaded
 * as-is. Identity/master-data columns (kdptimsmh, namafakultas, kodeprog,
 * etc.) are accepted but ignored — they're informational only in the export.
 */
class TracerResponsesImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $updated = 0;

    /** @var list<array{row: int, reason: string}> */
    public array $skipped = [];

    public function __construct(private readonly User $importedBy) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2; // 0-based collection index + 1 for the heading row
            $nim = TracerValueParser::str($row['nimhsmsmh'] ?? null);

            if ($nim === null) {
                $this->skipped[] = ['row' => $line, 'reason' => 'Kolom nimhsmsmh kosong'];

                continue;
            }

            $alumni = Alumni::where('nim', $nim)->first();

            if (! $alumni) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} tidak ditemukan"];

                continue;
            }

            if (! $this->importedBy->can('fillTracer', $alumni)) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} di luar cakupan Anda"];

                continue;
            }

            $data = $this->mapRow($row);
            $data['submitted_by_user_id'] = $this->importedBy->id;
            $data['submitted_at'] = TracerValueParser::date($row['waktu_update'] ?? null) ?? now();

            $alumni->tracerResponse()->updateOrCreate(['alumni_id' => $alumni->id], $data);
            $this->updated++;
        }
    }

    private function mapRow(Collection $row): array
    {
        $workProvinceId = $this->resolveProvince($row['propinsi_tempat_bekerja'] ?? null, $row['f5a1'] ?? null);
        $workCityId = $this->resolveCity($workProvinceId, $row['kabupaten_tempat_bekerja'] ?? null, $row['f5a2'] ?? null);

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

    private function resolveProvince(mixed $name, mixed $code): ?int
    {
        $name = TracerValueParser::str($name);
        $code = TracerValueParser::str($code);

        if ($name !== null) {
            $clean = trim(str_replace('Prov.', '', $name));
            $province = Province::where('name', $clean)->first();
            if ($province) {
                return $province->id;
            }
        }

        return $code !== null ? Province::where('code', $code)->value('id') : null;
    }

    private function resolveCity(?int $provinceId, mixed $name, mixed $code): ?int
    {
        $name = TracerValueParser::str($name);
        $code = TracerValueParser::str($code);

        if ($provinceId && $name !== null) {
            $city = City::where('province_id', $provinceId)->where('name', $name)->first();
            if ($city) {
                return $city->id;
            }
        }

        return $code !== null ? City::where('code', $code)->value('id') : null;
    }
}
