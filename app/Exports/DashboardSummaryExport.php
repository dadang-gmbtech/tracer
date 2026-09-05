<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DashboardSummaryExport implements FromArray, WithHeadings
{
    /**
     * @param  array{years: list<int>, data: array<int, array<string, mixed>>}  $summary
     */
    public function __construct(private readonly array $summary) {}

    public function headings(): array
    {
        return [
            'Tahun', 'Jumlah Alumni', 'Responden', 'Bekerja', 'Wiraswasta', 'Melanjutkan Studi',
            '% Responden', '% IKU Berdasar Responden', '% IKU Berdasar Lulusan',
            'Rata-rata Penghasilan', 'Rata-rata Waktu Tunggu (Bulan)',
        ];
    }

    public function array(): array
    {
        return collect($this->summary['years'])->map(function (int $year) {
            $row = $this->summary['data'][$year];

            return [
                $year,
                $row['jumlah_alumni'],
                $row['responden'],
                $row['bekerja'],
                $row['wiraswasta'],
                $row['melanjutkan_studi'],
                $row['persentase_responden'],
                $row['iku_berdasar_responden'],
                $row['iku_berdasar_lulusan'],
                $row['rata_rata_penghasilan'],
                $row['rata_rata_waktu_tunggu'],
            ];
        })->all();
    }
}
