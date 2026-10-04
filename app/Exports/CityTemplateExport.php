<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CityTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['Kode Wilayah Negara', 'Negara', 'Kode Wilayah Provinsi', 'Provinsi', 'Kode Wilayah Kota/Kabupaten', 'Kota/Kabupaten'];
    }

    public function array(): array
    {
        return [
            ['ID', 'Indonesia', '020000', 'Prov. Jawa Barat', '020500', 'Kab. Bekasi'],
        ];
    }
}
