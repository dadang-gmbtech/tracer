<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SurveyorTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['nama', 'email', 'no_telp', 'kode_fakultas'];
    }

    public function array(): array
    {
        return [
            ['Budi Santoso', 'budi.surveyor@unsoed.ac.id', '081234567890', 'H'],
        ];
    }
}
