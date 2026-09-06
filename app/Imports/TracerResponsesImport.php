<?php

namespace App\Imports;

use App\Models\Alumni;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use App\Services\AlumniProvisioningService;
use App\Support\ImportScopeGuard;
use App\Support\TracerFieldCodes;
use App\Support\TracerValueParser;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk-updates tracer_responses matched by NIM (nimhsmsmh). If the NIM isn't
 * registered yet, the alumni (and its faculty/program studi, if new) is
 * created from the identity columns in the same row — the row carries the
 * full national export format (kdptimsmh..npwp, kodefak..namaprogdikti), so
 * one upload can bootstrap alumni data and their tracer answers together.
 *
 * Note: this never creates a User login account (no real tanggal_lahir is
 * available from this format) — alumni created this way can't log in via
 * NIM+DOB until a real academic-system integration or an admin sets one.
 *
 * Column set otherwise mirrors TracerResponsesExport exactly (minus f504),
 * so an exported file can be edited and re-uploaded as-is.
 */
class TracerResponsesImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
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
            $nim = TracerValueParser::str($row['nimhsmsmh'] ?? null);

            if ($nim === null) {
                $this->skipped[] = ['row' => $line, 'reason' => 'Kolom nimhsmsmh kosong'];

                continue;
            }

            $alumni = Alumni::where('nim', $nim)->first();
            $isNew = $alumni === null;

            if ($alumni) {
                if (! $this->importedBy->can('fillTracer', $alumni)) {
                    $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} di luar cakupan Anda"];

                    continue;
                }
            } else {
                if (! $this->authorizedForRow($row)) {
                    $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} (baru) di luar cakupan Anda"];

                    continue;
                }

                $alumni = $this->findOrCreateAlumni($nim, $row);

                if (! $alumni) {
                    $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim}: data fakultas/prodi tidak lengkap"];

                    continue;
                }
            }

            $data = $this->mapRow($row);
            $data['submitted_by_user_id'] = $this->importedBy->id;
            $data['submitted_at'] = TracerValueParser::date($row['waktu_update'] ?? null) ?? now();

            $alumni->tracerResponse()->updateOrCreate(['alumni_id' => $alumni->id], $data);
            $isNew ? $this->created++ : $this->updated++;
        }
    }

    /**
     * Whether the importing user may create a brand-new alumni for this row,
     * based on the row's own kodefak/kodeprog against the actor's scope
     * (an existing alumni's own faculty/prodi is checked via the fillTracer
     * policy instead — see collection() above).
     */
    private function authorizedForRow(Collection $row): bool
    {
        return ImportScopeGuard::allows(
            $this->importedBy,
            TracerValueParser::str($row['kodefak'] ?? null),
            TracerValueParser::str($row['kodeprog'] ?? null),
        );
    }

    private function findOrCreateAlumni(string $nim, Collection $row): ?Alumni
    {
        $facultyCode = TracerValueParser::str($row['kodefak'] ?? null);
        $prodiCode = TracerValueParser::str($row['kodeprog'] ?? null);

        if ($facultyCode === null || $prodiCode === null) {
            return null;
        }

        return (new AlumniProvisioningService)->findOrCreate($nim, [
            'nama' => TracerValueParser::str($row['nmmhsmsmh'] ?? null),
            'email' => TracerValueParser::str($row['emailmsmh'] ?? null) ?? TracerValueParser::str($row['emailunsoed'] ?? null),
            'faculty_code' => $facultyCode,
            'faculty_name' => TracerValueParser::str($row['namafakultas'] ?? null),
            'prodi_code' => $prodiCode,
            'prodi_name' => TracerValueParser::str($row['namaprogdikti'] ?? null),
            'prodi_level' => TracerValueParser::str($row['namajenjang'] ?? null),
            'graduation_year' => TracerValueParser::int($row['tahun_lulus'] ?? null),
            'nik' => TracerValueParser::str($row['nik'] ?? null),
            'npwp' => TracerValueParser::str($row['npwp'] ?? null),
            'phone' => TracerValueParser::str($row['telpomsmh'] ?? null),
        ]);
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
