<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Reads via FromQuery + WithChunkReading rather than loading every matching
 * response into memory at once — see TracerResponsesExport for why. Column
 * names for the ratings (q1_kerja_sama_tim, ...) match
 * EmployerResponseImport/EmployerResponseTemplateExport, so an exported file
 * can be edited and re-uploaded as-is.
 */
class EmployerResponseExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        // orderBy('id') is required for chunked pagination to be safe (see
        // FromQuery's docblock).
        return $this->query->orderBy('id');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'nim', 'nama_alumni', 'fakultas', 'program_studi', 'tahun_lulus',
            'nama_pengisi', 'jabatan', 'nama_perusahaan', 'alamat_perusahaan', 'no_telp',
            'q1_kerja_sama_tim', 'q2_pengembangan_diri', 'q3_komunikasi', 'q4_teknologi_informasi',
            'q5_bahasa_asing', 'q6_keahlian', 'q7_integritas', 'tanggal_diisi',
        ];
    }

    public function map($response): array
    {
        $alumni = $response->alumni;

        return [
            $alumni?->nim,
            $alumni?->nama,
            $alumni?->faculty?->name,
            $alumni?->studyProgram?->name,
            $alumni?->graduation_year,
            $response->nama_pengisi,
            $response->jabatan,
            $response->nama_perusahaan,
            $response->alamat_perusahaan,
            $response->no_telp,
            $response->q1_kerja_sama_tim,
            $response->q2_pengembangan_diri,
            $response->q3_komunikasi,
            $response->q4_teknologi_informasi,
            $response->q5_bahasa_asing,
            $response->q6_keahlian,
            $response->q7_integritas,
            $response->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
