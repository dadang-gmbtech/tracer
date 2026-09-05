<?php

namespace App\Imports;

use App\Models\Province;
use App\Models\UmpSalary;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Template columns: kode_provinsi, tahun, nominal. */
class UmpImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $provinceId = Province::where('code', trim((string) ($row['kode_provinsi'] ?? '')))->value('id');
            $year = (int) ($row['tahun'] ?? 0);
            $amount = (float) preg_replace('/[^0-9.]/', '', (string) ($row['nominal'] ?? ''));

            if (! $provinceId || $year < 2000 || $amount <= 0) {
                continue;
            }

            UmpSalary::updateOrCreate(
                ['province_id' => $provinceId, 'year' => $year],
                ['amount' => $amount]
            );

            $this->imported++;
        }
    }
}
