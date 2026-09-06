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
            'nimhsmsmh' => 'A1A021001',
            'nmmhsmsmh' => 'Contoh Nama Alumni',
            'tahun_lulus' => '2024',
            'kodefak' => 'A',
            'namafakultas' => 'Pertanian',
            'kodeprog' => '54401',
            'namajenjang' => 'S1',
            'namaprogdikti' => 'Agroteknologi',
            'f8' => '1',
            'f502' => '3',
            'f505' => '4500000',
        ]);

        return [array_values($row)];
    }
}
