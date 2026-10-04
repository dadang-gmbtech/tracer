<?php

namespace App\Imports;

use App\Models\Country;
use App\Models\Province;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Template columns: kode_wilayah_negara, negara, kode_wilayah_provinsi,
 * provinsi — matches the "Daftar Provinsi" sheet of the official master
 * wilayah spreadsheet exactly (names there are prefixed "Prov. ", stripped
 * here before saving). Rows from the sheet's other two sheets are skipped
 * (see the column-count guard, mirroring CountriesImport/CitiesImport).
 *
 * Matches existing rows by name first, not by code — the national code
 * scheme changed over time (older rows in this app may carry a different
 * code format), and matching by name means re-running this import just
 * corrects the code/country on the existing row instead of creating a
 * duplicate that would orphan every alumni/tracer_responses row already
 * pointing at the original province id.
 */
class ProvincesImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (count($row) !== 4) {
                continue;
            }

            $code = trim((string) ($row['kode_wilayah_provinsi'] ?? ''));
            $name = $this->stripProvPrefix(trim((string) ($row['provinsi'] ?? '')));
            $countryCode = trim((string) ($row['kode_wilayah_negara'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $countryId = $countryCode !== '' ? Country::where('code', $countryCode)->value('id') : null;

            $province = Province::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
                ?? Province::where('code', $code)->first();

            if ($province) {
                $province->update(['code' => $code, 'name' => $name, 'country_id' => $countryId]);
                $this->updated++;
            } else {
                Province::create(['code' => $code, 'name' => $name, 'country_id' => $countryId]);
                $this->created++;
            }
        }
    }

    private function stripProvPrefix(string $name): string
    {
        return preg_replace('/^Prov\.\s*/i', '', $name);
    }
}
