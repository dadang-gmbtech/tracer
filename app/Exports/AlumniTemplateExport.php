<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Mirrors the columns of the real SIAKAD-style alumni export (nim, nama,
 * tahunlulu, emailunsoed, emailpersonal, npwp, tgllahir, notelp, kodeprog,
 * namajenjang, namaprogdikti). kode_fakultas/nama_fakultas are optional —
 * only needed if the program studi isn't registered yet.
 */
class AlumniTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'nim', 'nama', 'tahunlulu', 'emailunsoed', 'emailpersonal', 'npwp', 'tgllahir', 'notelp',
            'kodeprog', 'namajenjang', 'namaprogdikti', 'kode_fakultas', 'nama_fakultas',
        ];
    }

    public function array(): array
    {
        return [
            [
                'A1A021001', 'Contoh Nama Alumni', '2024', 'contoh@mhs.unsoed.ac.id', 'contoh.pribadi@gmail.com',
                '', '2001-05-17', '081234567890', '55201', 'S1', 'Informatika', 'H', 'Teknik',
            ],
        ];
    }
}
