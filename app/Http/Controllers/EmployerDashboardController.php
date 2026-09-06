<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Services\EmployerDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $summary = $this->dashboard->summary($user, $filters);
        $years = $summary['years'];
        $recapYear = (int) ($request->integer('recap_year') ?: (count($years) ? end($years) : now()->year));

        return view('employer.dashboard', [
            'summary' => $summary,
            'facultyRecap' => $this->dashboard->facultyRecap($user, $recapYear, $filters),
            'recapYear' => $recapYear,
            'faculties' => $isUniversityScoped ? Faculty::orderBy('name')->get() : collect(),
            'programStudies' => $isUniversityScoped ? StudyProgram::orderBy('name')->get() : collect(),
        ]);
    }
}
