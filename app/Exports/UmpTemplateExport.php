<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UmpTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['kode_provinsi', 'tahun', 'nominal'];
    }

    public function array(): array
    {
        return [
            ['33', now()->year, '2036947'],
        ];
    }
}
