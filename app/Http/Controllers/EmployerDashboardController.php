<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Services\EmployerDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployerDashboardController extends Controller
{
    public function __construct(private readonly EmployerDashboardService $dashboard) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('Alumni')) {
            return redirect($user->postLoginUrl());
        }

        Gate::authorize('view-dashboard');

        $filters = array_filter([
            'faculty_id' => $request->integer('faculty_id') ?: null,
            'program_study_id' => $request->integer('program_study_id') ?: null,
        ]);

        $isUniversityScoped = $user->hasAnyRole(['Super Admin', 'Admin Universitas', 'Pimpinan Universitas']);

        // summary() always runs — cheap, since EmployerDashboardService::
        // loadResponses() memoizes the scoped query to a single DB
        // round-trip per request — so recapYear resolves from the REAL
        // available years before anything gets cached below.
        $summary = $this->dashboard->summary($user, $filters);
        $years = $summary['years'];
        $recapYear = (int) ($request->integer('recap_year') ?: (count($years) ? end($years) : now()->year));

        $compareFacultyIds = array_values(array_filter(array_map('intval', (array) $request->input('compare_faculty_ids', []))));

        $cacheKey = 'employer-dashboard:'.$user->id.':'.md5(serialize($filters + compact('recapYear', 'compareFacultyIds')));

        $data = Cache::remember($cacheKey, now()->addMinutes(5), fn () => [
            'facultyRecap' => $this->dashboard->facultyRecap($user, $recapYear, $filters),
            'facultyComparison' => $compareFacultyIds ? $this->dashboard->indeksPerFacultyPerYear($user, $compareFacultyIds, $filters) : null,
        ]);

        return view('employer.dashboard', [
            ...$data,
            'summary' => $summary,
            'recapYear' => $recapYear,
            'selectedFacultyIds' => $compareFacultyIds,
            'faculties' => $isUniversityScoped ? Faculty::orderBy('name')->get() : collect(),
            'programStudies' => $isUniversityScoped ? StudyProgram::orderBy('name')->get() : collect(),
        ]);
    }
}
