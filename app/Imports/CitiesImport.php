<?php

namespace App\Imports;

use App\Models\City;
use App\Models\Province;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Template columns: kode_provinsi, kode_kota, nama_kota. */
class CitiesImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $provinceCode = trim((string) ($row['kode_provinsi'] ?? ''));
            $name = trim((string) ($row['nama_kota'] ?? ''));

            $provinceId = Province::where('code', $provinceCode)->value('id');

            if (! $provinceId || $name === '') {
                continue;
            }

            City::updateOrCreate(
                ['province_id' => $provinceId, 'name' => $name],
                ['code' => trim((string) ($row['kode_kota'] ?? '')) ?: null]
            );

            $this->imported++;
        }
    }
}
