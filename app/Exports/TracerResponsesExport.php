<?php

namespace App\Exports;

use App\Support\TracerFieldCodes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Column order matches Contoh data.csv (the national Kemdiktisaintek tracer
 * export), minus f504, so the file can be edited and re-imported via
 * TracerResponsesImport.
 */
class TracerResponsesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $alumniQuery) {}

    public function collection(): Collection
    {
        return $this->alumniQuery->with(['faculty', 'studyProgram', 'tracerResponse.workProvince', 'tracerResponse.workCity'])->get();
    }

    public function headings(): array
    {
        return TracerFieldCodes::exportColumns();
    }

    public function map($alumni): array
    {
        $r = $alumni->tracerResponse;

        $row = [
            config('tracer.kode_pt'),
            $alumni->studyProgram?->code,
            $alumni->nim,
            $alumni->nama,
            $alumni->phone,
            $alumni->email,
            $alumni->graduation_year,
            $alumni->nik,
            $alumni->npwp,
        ];

        foreach (TracerFieldCodes::codes() as $code) {
            $row[] = $r?->{$code};
        }

        $row[] = $alumni->email;
        $row[] = $alumni->faculty?->code;
        $row[] = $alumni->faculty?->name;
        $row[] = $alumni->studyProgram?->code;
        $row[] = $alumni->studyProgram?->level;
        $row[] = $alumni->studyProgram?->name;
        $row[] = $r?->submitted_at?->format('Y-m-d H:i:s');
        $row[] = $r?->workProvince?->name;
        $row[] = $r?->workCity?->name;
        $row[] = null; // datarespondendikti_id — not synced with the national system
        $row[] = null; // idkuesionerdikti — not synced with the national system

        return $row;
    }
}
