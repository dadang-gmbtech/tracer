<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CountryTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['Kode Wilayah Negara', 'Negara'];
    }

    public function array(): array
    {
        return [
            ['ID', 'Indonesia'],
        ];
    }
}
