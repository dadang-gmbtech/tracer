<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CityTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['kode_provinsi', 'kode_kota', 'nama_kota'];
    }

    public function array(): array
    {
        return [
            ['32', '3216', 'Kab. Bekasi'],
        ];
    }
}
