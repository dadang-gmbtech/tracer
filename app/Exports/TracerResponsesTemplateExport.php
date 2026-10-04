<?php

namespace App\Exports;

use App\Support\TracerFieldCodes;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * A lightweight starting point for a new tracer-studi upload — headers plus
 * one example row, not a dump of existing data. That's what
 * ExportController::tracer() is for (see the "Ekspor Data Tracer" menu
 * item) — downloading everything currently in the system just to see the
 * column format was both confusing (it isn't a "template") and, for a
 * university with thousands of alumni, unnecessarily heavy.
 */
class TracerResponsesTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return TracerFieldCodes::exportColumns();
    }

    public function array(): array
    {
        $row = array_fill_keys(TracerFieldCodes::exportColumns(), '');

        $row = array_merge($row, [
            'Kode Prodi' => '54401',
            'NIM/Nomor Mhs' => 'A1A021001',
            'Nama Mhs' => 'Contoh Nama Alumni',
            "Tahun Lulus\n Keluar" => '2024',
            'F8' => '1',
            'F502' => '3',
            'F505' => '4500000',
        ]);

        return [array_values($row)];
    }
}
