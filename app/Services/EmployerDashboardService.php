<?php

namespace App\Services;

use App\Models\EmployerResponse;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Aggregates "Pengguna Alumni" (employer) feedback — the 7-question survey
 * in Form Pengguna Alumni.pdf, rated 1 (Sangat Baik) to 4 (Kurang). Mirrors
 * DashboardService's shape (years/data, facultyRecap) but for employer
 * responses instead of the alumni's own tracer answers.
 */
class EmployerDashboardService
{
    /**
     * @var array<string, string>
     */
    public const QUESTIONS = [
        'q1_kerja_sama_tim' => 'Kerja Sama Tim',
        'q2_pengembangan_diri' => 'Pengembangan Diri',
        'q3_komunikasi' => 'Komunikasi',
        'q4_teknologi_informasi' => 'Teknologi Informasi',
        'q5_bahasa_asing' => 'Bahasa Asing',
        'q6_keahlian' => 'Keahlian',
        'q7_integritas' => 'Integritas',
    ];

    /**
     * @param  array{faculty_id?: int, program_study_id?: int}  $filters
     */
    public function summary(User $user, array $filters = []): array
    {
        $responses = $this->scopedResponses($user, $filters)->get();
        $years = $this->yearRange($responses);

        $perYear = [];
        foreach ($years as $year) {
            $perYear[$year] = $this->aggregateFor(
                $responses->filter(fn (EmployerResponse $r) => (int) $r->alumni->graduation_year === $year)
            );
        }

        return [
            'years' => $years,
            'data' => $perYear,
            'total' => $this->aggregateFor($responses),
        ];
    }

    /**
     * @param  array{faculty_id?: int, program_study_id?: int}  $filters
     */
    public function facultyRecap(User $user, array $filters = []): array
    {
        $responses = $this->scopedResponses($user, $filters)->get();

        $rows = $responses->groupBy(fn (EmployerResponse $r) => $r->alumni->faculty_id)
            ->map(fn (Collection $group) => [
                'faculty' => $group->first()->alumni->faculty?->name ?? '-',
                ...$this->aggregateFor($group),
            ])
            ->sortBy('faculty')
            ->values();

        return [
            'rows' => $rows,
            'total' => $this->aggregateFor($responses),
        ];
    }

    /**
     * @param  Collection<int, EmployerResponse>  $responses
     */
    private function aggregateFor(Collection $responses): array
    {
        $perQuestion = [];

        foreach (self::QUESTIONS as $field => $label) {
            $values = $responses->pluck($field)->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v);
            $avg = $values->isEmpty() ? null : round($values->avg(), 2);

            $perQuestion[$field] = [
                'label' => $label,
                'rata_rata' => $avg,
                // 1 (Sangat Baik) -> 4 (Kurang) flipped into a 0-100 index where higher is better.
                'indeks' => $avg === null ? 0.0 : round(((4 - $avg) / 3) * 100, 1),
                'distribusi' => [
                    1 => $values->filter(fn ($v) => $v === 1)->count(),
                    2 => $values->filter(fn ($v) => $v === 2)->count(),
                    3 => $values->filter(fn ($v) => $v === 3)->count(),
                    4 => $values->filter(fn ($v) => $v === 4)->count(),
                ],
            ];
        }

        $indeksTerisi = collect($perQuestion)->pluck('rata_rata')->filter(fn ($v) => $v !== null);

        return [
            'jumlah_respon' => $responses->count(),
            'per_pertanyaan' => $perQuestion,
            'indeks_keseluruhan' => $indeksTerisi->isEmpty() ? 0.0 : round(collect($perQuestion)->pluck('indeks')->avg(), 1),
        ];
    }

    private function scopedResponses(User $user, array $filters)
    {
        return EmployerResponse::query()
            ->whereHas('alumni', function ($query) use ($user, $filters) {
                $query->visibleTo($user);

                if (! empty($filters['faculty_id'])) {
                    $query->where('faculty_id', $filters['faculty_id']);
                }

                if (! empty($filters['program_study_id'])) {
                    $query->where('program_study_id', $filters['program_study_id']);
                }
            })
            ->with(['alumni.faculty', 'alumni.studyProgram']);
    }

    /**
     * @param  Collection<int, EmployerResponse>  $responses
     */
    private function yearRange(Collection $responses): array
    {
        $years = $responses->pluck('alumni.graduation_year')->filter()->map(fn ($y) => (int) $y)->unique();

        if ($years->isEmpty()) {
            return [(int) now()->format('Y')];
        }

        return range($years->min(), $years->max());
    }
}
