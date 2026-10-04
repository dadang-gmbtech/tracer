<?php

namespace App\Exports;

use App\Support\TracerFieldCodes;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Column order matches the official "Pelaporan Tracer Study" national
 * reporting template exactly, minus f504/f506, so the file can be uploaded
 * straight to the national system and an edited copy re-imported via
 * TracerResponsesImport.
 *
 * Reads via FromQuery + WithChunkReading rather than loading every matching
 * alumni into memory at once (FromCollection) — a university-wide export
 * with thousands of alumni exceeded PHP's memory_limit building the whole
 * result set (with its eager-loaded relations) in memory before handing it
 * to the writer.
 */
class TracerResponsesExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $alumniQuery) {}

    public function query(): Builder
    {
        // orderBy('id') is required for chunked pagination to be safe (see
        // FromQuery's docblock) — without a unique, deterministic order,
        // LIMIT/OFFSET paging can skip or duplicate rows across chunks.
        return $this->alumniQuery
            ->with(['studyProgram', 'tracerResponse'])
            ->orderBy('id');
    }

    public function chunkSize(): int
    {
        return 500;
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

        return $row;
    }
}
