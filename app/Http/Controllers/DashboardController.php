<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('Alumni')) {
            return redirect($user->postLoginUrl());
        }

        $jenjang = array_values(array_intersect((array) $request->input('jenjang', []), ['D3', 'S1', 'S2', 'S3']));

        $filters = array_filter([
            'faculty_id' => $request->integer('faculty_id') ?: null,
            'program_study_id' => $request->integer('program_study_id') ?: null,
            'jenjang' => $jenjang ?: null,
        ]);

        $summary = $this->dashboard->summary($user, $filters);

        $years = $summary['years'];
        $yearB = (int) ($request->integer('year_b') ?: (count($years) ? end($years) : now()->year));
        $yearA = (int) ($request->integer('year_a') ?: (count($years) > 1 ? $years[count($years) - 2] : $yearB));
        $recapYear = (int) ($request->integer('recap_year') ?: (count($years) ? end($years) : now()->year));

        return view('dashboard.index', [
            'summary' => $summary,
            'monthlyBreakdown' => $this->dashboard->monthlyBreakdown($user, $yearA, $yearB, $filters),
            'facultyRecap' => $this->dashboard->facultyRecap($user, $recapYear, $filters),
            'provincePoints' => $this->dashboard->alumniByProvince($user, $filters),
            'yearA' => $yearA,
            'yearB' => $yearB,
            'recapYear' => $recapYear,
            'faculties' => $user->hasAnyRole(['Super Admin', 'Admin Universitas', 'Pimpinan Universitas']) ? Faculty::orderBy('name')->get() : collect(),
            'programStudies' => $user->hasAnyRole(['Super Admin', 'Admin Universitas', 'Pimpinan Universitas']) ? StudyProgram::orderBy('name')->get() : collect(),
        ]);
    }
}
