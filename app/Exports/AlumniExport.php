<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Reads via FromQuery + WithChunkReading rather than loading every matching
 * alumni into memory at once — see TracerResponsesExport for why.
 */
class AlumniExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $alumniQuery) {}

    public function query(): Builder
    {
        // orderBy('id') is required for chunked pagination to be safe (see
        // FromQuery's docblock).
        return $this->alumniQuery->with(['faculty', 'studyProgram', 'tracerResponse'])->orderBy('id');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return ['NIM', 'Nama', 'Email', 'Fakultas', 'Program Studi', 'Tahun Lulus', 'Status Tracer', 'Sudah Mengisi'];
    }

    public function map($alumni): array
    {
        return [
            $alumni->nim,
            $alumni->nama,
            $alumni->email,
            $alumni->faculty?->name,
            $alumni->studyProgram?->name,
            $alumni->graduation_year,
            $this->statusLabel($alumni->tracerResponse?->f8),
            $alumni->tracerResponse ? 'Ya' : 'Belum',
        ];
    }

    private function statusLabel(?int $f8): string
    {
        return match ($f8) {
            1 => 'Bekerja',
            2 => 'Belum memungkinkan bekerja',
            3 => 'Wiraswasta',
            4 => 'Melanjutkan Pendidikan',
            5 => 'Mencari kerja',
            default => '-',
        };
    }
}
