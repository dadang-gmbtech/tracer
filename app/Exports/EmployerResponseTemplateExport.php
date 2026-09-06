<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Mirrors employer_responses' own columns (minus alumni_id, plus nim to
 * match the alumni). Ratings are 1 (Sangat Baik), 2 (Baik), 3 (Cukup),
 * 4 (Kurang) — same scale as the public Form Pengguna Alumni.
 */
class EmployerResponseTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'nim', 'nama_pengisi', 'jabatan', 'nama_perusahaan', 'alamat_perusahaan', 'no_telp',
            'q1_kerja_sama_tim', 'q2_pengembangan_diri', 'q3_komunikasi', 'q4_teknologi_informasi',
            'q5_bahasa_asing', 'q6_keahlian', 'q7_integritas',
        ];
    }

    public function array(): array
    {
        return [
            [
                'A1A021001', 'Contoh Nama Pengisi', 'HRD Manager', 'PT Contoh Sejahtera',
                'Jl. Contoh No. 1, Purwokerto', '081234567890', 1, 2, 1, 2, 3, 1, 1,
            ],
        ];
    }
}
