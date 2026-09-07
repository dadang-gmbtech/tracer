<?php

namespace App\Http\Controllers;

use App\Exports\AlumniExport;
use App\Exports\DashboardSummaryExport;
use App\Exports\EmployerResponseExport;
use App\Exports\TracerResponsesExport;
use App\Models\Alumni;
use App\Models\EmployerResponse;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function tracerForm(Request $request): View
    {
        return view('exports.tracer', $this->filterFormData($request));
    }

    public function tracer(Request $request): BinaryFileResponse
    {
        Gate::authorize('export-data');
        $this->raiseLimitsForLargeExport();

        $query = $this->scopedAlumniQuery($request);

        return Excel::download(new TracerResponsesExport($query), 'tracer-responses-'.now()->format('Ymd-His').'.xlsx');
    }

    public function alumniForm(Request $request): View
    {
        return view('exports.alumni', $this->filterFormData($request));
    }

    public function alumni(Request $request): BinaryFileResponse
    {
        Gate::authorize('export-data');
        $this->raiseLimitsForLargeExport();

        $query = $this->scopedAlumniQuery($request);

        return Excel::download(new AlumniExport($query), 'alumni-'.now()->format('Ymd-His').'.xlsx');
    }

    public function employerResponsesForm(Request $request): View
    {
        return view('exports.employer', $this->filterFormData($request));
    }

    public function employerResponses(Request $request): BinaryFileResponse
    {
        Gate::authorize('export-data');
        $this->raiseLimitsForLargeExport();

        $query = $this->scopedEmployerResponseQuery($request);

        return Excel::download(new EmployerResponseExport($query), 'pengguna-alumni-'.now()->format('Ymd-His').'.xlsx');
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
     * Lets the admin pick a graduation-year range (and, for university-wide
     * roles, a faculty/prodi) before downloading — an unfiltered export of a
     * university with thousands of alumni is both slow and, even after
     * chunked reading, memory-heavy (see raiseLimitsForLargeExport()).
     *
     * @return array{years: Collection, faculties: Collection, programStudies: Collection}
     */
    private function filterFormData(Request $request): array
    {
        Gate::authorize('export-data');

        $user = $request->user();
        $isUniversityScoped = $user->hasAnyRole(['Super Admin', 'Admin Universitas', 'Pimpinan Universitas']);

        return [
            'years' => Alumni::query()->visibleTo($user)->distinct()->orderBy('graduation_year')->pluck('graduation_year')->filter()->values(),
            'faculties' => $isUniversityScoped ? Faculty::orderBy('name')->get() : collect(),
            'programStudies' => $isUniversityScoped ? StudyProgram::orderBy('name')->get() : collect(),
        ];
    }

    /**
     * TracerResponsesExport/AlumniExport/EmployerResponseExport already read
     * via FromQuery + WithChunkReading, which keeps Eloquent's own memory
     * use bounded — but PhpSpreadsheet still has to hold a Cell object for
     * every row/column of the final workbook in memory before it can write
     * the file (there's no true streaming XLSX writer), and each Cell
     * carries real overhead. A university-wide export (thousands of alumni
     * x ~90 tracer columns) exceeds PHP's default memory_limit on that
     * alone. Raised only for this request, not globally.
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

        if ($request->filled('graduation_year_from')) {
            $query->where('graduation_year', '>=', $request->integer('graduation_year_from'));
        }

        if ($request->filled('graduation_year_to')) {
            $query->where('graduation_year', '<=', $request->integer('graduation_year_to'));
        }

        $jenjang = $this->selectedJenjang($request);

        if ($jenjang) {
            $query->whereHas('studyProgram', fn ($q) => $q->whereIn('level', $jenjang));
        }

        return $query;
    }

    private function scopedEmployerResponseQuery(Request $request)
    {
        $user = $request->user();
        $jenjang = $this->selectedJenjang($request);

        return EmployerResponse::query()
            ->with(['alumni.faculty', 'alumni.studyProgram'])
            ->whereHas('alumni', function ($query) use ($request, $user, $jenjang) {
                $query->visibleTo($user);

                if ($request->filled('faculty_id')) {
                    $query->where('faculty_id', $request->integer('faculty_id'));
                }

                if ($request->filled('program_study_id')) {
                    $query->where('program_study_id', $request->integer('program_study_id'));
                }

                if ($request->filled('graduation_year_from')) {
                    $query->where('graduation_year', '>=', $request->integer('graduation_year_from'));
                }

                if ($request->filled('graduation_year_to')) {
                    $query->where('graduation_year', '<=', $request->integer('graduation_year_to'));
                }

                if ($jenjang) {
                    $query->whereHas('studyProgram', fn ($q) => $q->whereIn('level', $jenjang));
                }
            });
    }

    /**
     * @return list<string>
     */
    private function selectedJenjang(Request $request): array
    {
        return array_values(array_intersect((array) $request->input('jenjang', []), ['D3', 'S1', 'S2', 'S3']));
    }
}
