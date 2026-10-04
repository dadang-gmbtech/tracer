<?php

namespace App\Imports;

use App\Models\City;
use App\Models\Province;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Template columns: kode_wilayah_negara, negara, kode_wilayah_provinsi,
 * provinsi, kode_wilayah_kotakabupaten, kotakabupaten — matches the
 * "Daftar Kota atau Kabupaten" sheet of the official master wilayah
 * spreadsheet exactly ("Kota/Kabupaten" loses its slash once Laravel Excel
 * slugs the heading). Rows from the sheet's other two sheets are skipped
 * (see the column-count guard, mirroring CountriesImport/ProvincesImport).
 *
 * Matches existing rows by name within the resolved province, not by code,
 * for the same reason as ProvincesImport — re-running this import corrects
 * the code on the existing row rather than creating a duplicate.
 */
class CitiesImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (count($row) !== 6) {
                continue;
            }

            $cityCode = trim((string) ($row['kode_wilayah_kotakabupaten'] ?? ''));
            $cityName = trim((string) ($row['kotakabupaten'] ?? ''));
            $provinceCode = trim((string) ($row['kode_wilayah_provinsi'] ?? ''));
            $provinceName = $this->stripProvPrefix(trim((string) ($row['provinsi'] ?? '')));

            if ($cityCode === '' || $cityName === '') {
                continue;
            }

            $provinceId = Province::where('code', $provinceCode)->value('id')
                ?? Province::whereRaw('LOWER(name) = ?', [mb_strtolower($provinceName)])->value('id');

            if (! $provinceId) {
                $this->skipped++;

                continue;
            }

            $city = City::where('province_id', $provinceId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($cityName)])
                ->first();

            if ($city) {
                $city->update(['code' => $cityCode]);
                $this->updated++;
            } else {
                City::create(['province_id' => $provinceId, 'code' => $cityCode, 'name' => $cityName]);
                $this->created++;
            }
        }
    }

    private function stripProvPrefix(string $name): string
    {
        return preg_replace('/^Prov\.\s*/i', '', $name);
    }
}
