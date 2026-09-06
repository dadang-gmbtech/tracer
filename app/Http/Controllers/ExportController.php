<?php

namespace App\Http\Controllers;

use App\Exports\AlumniExport;
use App\Exports\DashboardSummaryExport;
use App\Exports\TracerResponsesExport;
use App\Models\Alumni;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function tracer(Request $request): BinaryFileResponse
    {
        Gate::authorize('export-data');
        $this->raiseLimitsForLargeExport();

        $query = $this->scopedAlumniQuery($request);

        return Excel::download(new TracerResponsesExport($query), 'tracer-responses-'.now()->format('Ymd-His').'.xlsx');
    }

    public function alumni(Request $request): BinaryFileResponse
    {
        Gate::authorize('export-data');
        $this->raiseLimitsForLargeExport();

        $query = $this->scopedAlumniQuery($request);

        return Excel::download(new AlumniExport($query), 'alumni-'.now()->format('Ymd-His').'.xlsx');
    }

    public function dashboard(Request $request, DashboardService $dashboard): BinaryFileResponse
    {
        Gate::authorize('export-data');

        $filters = array_filter([
            'faculty_id' => $request->integer('faculty_id') ?: null,
            'program_study_id' => $request->integer('program_study_id') ?: null,
        ]);

        $summary = $dashboard->summary($request->user(), $filters);

        return Excel::download(new DashboardSummaryExport($summary), 'ringkasan-dashboard-'.now()->format('Ymd-His').'.xlsx');
    }

    /**
     * TracerResponsesExport/AlumniExport already read via FromQuery +
     * WithChunkReading, which keeps Eloquent's own memory use bounded — but
     * PhpSpreadsheet still has to hold a Cell object for every row/column of
     * the final workbook in memory before it can write the file (there's no
     * true streaming XLSX writer), and each Cell carries real overhead. A
     * university-wide export (thousands of alumni x ~90 tracer columns)
     * exceeds PHP's default memory_limit on that alone. Raised only for this
     * request, not globally.
     */
    private function raiseLimitsForLargeExport(): void
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(300);
    }

    private function scopedAlumniQuery(Request $request)
    {
        $query = Alumni::query()->visibleTo($request->user());

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->integer('faculty_id'));
        }

        if ($request->filled('program_study_id')) {
            $query->where('program_study_id', $request->integer('program_study_id'));
        }

        if ($request->filled('graduation_year')) {
            $query->where('graduation_year', $request->integer('graduation_year'));
        }

        return $query;
    }
}
