<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AlumniExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $alumniQuery) {}

    public function collection(): Collection
    {
        return $this->alumniQuery->with(['faculty', 'studyProgram', 'tracerResponse'])->get();
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
