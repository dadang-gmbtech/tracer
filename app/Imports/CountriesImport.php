<?php

namespace App\Imports;

use App\Models\Country;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Template columns: kode_wilayah_negara, negara — matches the "Daftar
 * Negara" sheet of the official master wilayah spreadsheet exactly, so that
 * file can be uploaded here as-is (rows from its other two sheets are
 * silently skipped since they don't have a "negara" column of their own
 * that resolves to a non-empty code/name pair — see ProvincesImport/
 * CitiesImport, which use the same tolerance in reverse).
 */
class CountriesImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Only the "Daftar Negara" sheet has nothing but these two
            // columns; rows from "Daftar Provinsi"/"Daftar Kota atau
            // Kabupaten" always carry extra columns, which WithHeadingRow
            // still parses into this same $row shape — skip them here by
            // requiring an exact 2-column shape.
            if (count($row) > 2) {
                continue;
            }

            $code = trim((string) ($row['kode_wilayah_negara'] ?? ''));
            $name = trim((string) ($row['negara'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $country = Country::where('code', $code)->first();

            if ($country) {
                $country->update(['name' => $name]);
                $this->updated++;
            } else {
                Country::create(['code' => $code, 'name' => $name]);
                $this->created++;
            }
        }
    }
}
