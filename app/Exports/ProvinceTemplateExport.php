<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProvinceTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['Kode Wilayah Negara', 'Negara', 'Kode Wilayah Provinsi', 'Provinsi'];
    }

    public function array(): array
    {
        return [
            ['ID', 'Indonesia', '010000', 'Prov. D.K.I. Jakarta'],
        ];
    }
}
