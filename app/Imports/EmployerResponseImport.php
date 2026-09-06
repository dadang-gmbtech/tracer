<?php

namespace App\Imports;

use App\Models\Alumni;
use App\Models\EmployerResponse;
use App\Models\User;
use App\Support\TracerValueParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk-adds "Pengguna Alumni" (employer) feedback collected offline — a
 * paper form, WhatsApp, or a phone call — matched to an existing alumni by
 * NIM. Never creates an alumni; a NIM not yet registered is skipped and
 * reported, same as the other imports.
 *
 * Column set mirrors employer_responses' own columns (minus alumni_id, plus
 * nim to look the alumni up). More than one response per alumni is allowed,
 * same as the public form — an alumni can be rated by more than one
 * employer over time, so each row always adds a new response rather than
 * updating a previous one.
 *
 * Each row's write runs in its own transaction — see TracerResponsesImport
 * for why (a single failed row must not roll back the rest of the file).
 */
class EmployerResponseImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    private const RATING_FIELDS = [
        'q1_kerja_sama_tim', 'q2_pengembangan_diri', 'q3_komunikasi',
        'q4_teknologi_informasi', 'q5_bahasa_asing', 'q6_keahlian', 'q7_integritas',
    ];

    public int $created = 0;

    /** @var list<array{row: int, reason: string}> */
    public array $skipped = [];

    public function __construct(private readonly User $importedBy) {}

    public function collection(Collection $rows): void
    {
        $nims = $rows->map(fn (Collection $row) => TracerValueParser::str($row['nim'] ?? null))->filter()->unique()->values();
        $alumniByNim = Alumni::whereIn('nim', $nims)->get()->keyBy('nim');

        foreach ($rows as $index => $row) {
            $line = $index + 2; // 0-based collection index + 1 for the heading row

            try {
                DB::transaction(fn () => $this->importRow($row, $line, $alumniByNim));
            } catch (\Throwable $e) {
                $this->skipped[] = ['row' => $line, 'reason' => 'Gagal disimpan: '.$e->getMessage()];
            }
        }
    }

    private function importRow(Collection $row, int $line, Collection $alumniByNim): void
    {
        $nim = TracerValueParser::str($row['nim'] ?? null);

        if ($nim === null) {
            $this->skipped[] = ['row' => $line, 'reason' => 'Kolom nim kosong'];

            return;
        }

        $alumni = $alumniByNim->get($nim);

        if (! $alumni) {
            $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} tidak ditemukan"];

            return;
        }

        if (! $this->importedBy->can('fillTracer', $alumni)) {
            $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim} di luar cakupan Anda"];

            return;
        }

        $ratings = [];

        foreach (self::RATING_FIELDS as $field) {
            $value = TracerValueParser::int($row[$field] ?? null);

            if ($value === null || $value < 1 || $value > 4) {
                $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim}: kolom {$field} harus diisi angka 1-4"];

                return;
            }

            $ratings[$field] = $value;
        }

        $namaPengisi = TracerValueParser::str($row['nama_pengisi'] ?? null);
        $namaPerusahaan = TracerValueParser::str($row['nama_perusahaan'] ?? null);

        if ($namaPengisi === null || $namaPerusahaan === null) {
            $this->skipped[] = ['row' => $line, 'reason' => "NIM {$nim}: kolom nama_pengisi dan nama_perusahaan wajib diisi"];

            return;
        }

        EmployerResponse::create([
            'alumni_id' => $alumni->id,
            'nama_pengisi' => $namaPengisi,
            'jabatan' => TracerValueParser::str($row['jabatan'] ?? null),
            'nama_perusahaan' => $namaPerusahaan,
            'alamat_perusahaan' => TracerValueParser::str($row['alamat_perusahaan'] ?? null),
            'no_telp' => TracerValueParser::str($row['no_telp'] ?? null),
            ...$ratings,
        ]);

        $this->created++;
    }
}
