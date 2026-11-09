<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\EmployerDashboardService;
use App\Services\ReportAnalysisService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generates a "Laporan Tracer Study" PDF from the same aggregated data
 * shown on the dashboard (DashboardService/EmployerDashboardService) —
 * an alternative to the manual PDF/Word uploads in ReportController, for
 * when nobody has written one and the numbers alone are enough.
 */
class AutoReportController extends Controller
{
    public function form(Request $request): View
    {
        Gate::authorize('export-data');

        $user = $request->user();
        $isUniversityScoped = $user->hasAnyRole(['Super Admin', 'Admin Universitas', 'Pimpinan Universitas']);

        return view('reports.auto-form', [
            'faculties' => $isUniversityScoped ? Faculty::orderBy('name')->get() : collect(),
            'programStudies' => $isUniversityScoped ? StudyProgram::orderBy('name')->get() : collect(),
            'defaultYear' => now()->year,
        ]);
    }

    public function download(
        Request $request,
        DashboardService $dashboard,
        EmployerDashboardService $employerDashboard,
        ReportAnalysisService $analyzer,
    ): Response {
        Gate::authorize('export-data');

        $user = $request->user();
        $year = $request->integer('year') ?: now()->year;

        $filters = array_filter([
            'faculty_id' => $request->integer('faculty_id') ?: null,
            'program_study_id' => $request->integer('program_study_id') ?: null,
            'jenjang' => array_values(array_intersect((array) $request->input('jenjang', []), StudyProgram::LEVELS)) ?: null,
        ]);

        $summary = $dashboard->summary($user, $filters);
        $trendYears = array_slice($summary['years'], -6);

        $current = $summary['data'][$year] ?? null;
        $previous = $summary['data'][$year - 1] ?? null;
        $facultyRecap = $dashboard->facultyRecap($user, $year, $filters);
        $breakdown = $dashboard->reportBreakdown($user, $year, $filters);
        $employerSummary = $employerDashboard->summary($user, $filters);

        $analysis = $current ? $analyzer->analyze(
            $year,
            $current,
            $previous,
            $breakdown,
            $previous ? $dashboard->reportBreakdown($user, $year - 1, $filters) : null,
            $facultyRecap['rows'],
            $employerSummary['total'],
        ) : [];

        $pdf = Pdf::loadView('reports.tracer-pdf', [
            'year' => $year,
            'scopeLabel' => $this->scopeLabel($user, $filters),
            'current' => $current,
            'summary' => $summary,
            'trendYears' => $trendYears,
            'facultyRecap' => $facultyRecap,
            'breakdown' => $breakdown,
            'analysis' => $analysis,
            'employerSummary' => $employerSummary,
            'generatedBy' => $user->name,
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->download("laporan-tracer-{$year}.pdf");
    }

    /**
     * @param  array{faculty_id?: int, program_study_id?: int, jenjang?: list<string>}  $filters
     */
    private function scopeLabel(User $user, array $filters): ?string
    {
        $unit = $this->unitLabel($user, $filters);
        $jenjang = empty($filters['jenjang']) ? null : 'Jenjang '.implode(', ', $filters['jenjang']);

        return implode(' — ', array_filter([$unit, $jenjang])) ?: null;
    }

    /**
     * @param  array{faculty_id?: int, program_study_id?: int, jenjang?: list<string>}  $filters
     */
    private function unitLabel(User $user, array $filters): ?string
    {
        if (! empty($filters['program_study_id'])) {
            return StudyProgram::find($filters['program_study_id'])?->name;
        }

        if (! empty($filters['faculty_id'])) {
            return Faculty::find($filters['faculty_id'])?->name;
        }

        if ($user->program_study_id) {
            return $user->studyProgram?->name;
        }

        if ($user->faculty_id) {
            return $user->faculty?->name;
        }

        return null; // Seluruh Universitas — no scope label needed.
    }
}
