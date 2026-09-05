<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlumniController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Alumni::class);

        $query = Alumni::query()->visibleTo($request->user())->with(['faculty', 'studyProgram', 'tracerResponse']);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%");
            });
        }

        if ($request->filled('graduation_year')) {
            $query->where('graduation_year', $request->input('graduation_year'));
        }

        if ($request->filled('status')) {
            $query->whereHas('tracerResponse', fn ($q) => $q->where('f8', $request->input('status')));
        }

        return view('alumni.index', [
            'alumni' => $query->orderBy('nama')->paginate(20)->withQueryString(),
            'graduationYears' => Alumni::visibleTo($request->user())->distinct()->orderByDesc('graduation_year')->pluck('graduation_year'),
        ]);
    }

    public function show(Request $request, Alumni $alumni): View
    {
        $this->authorize('view', $alumni);

        $alumni->load(['faculty', 'studyProgram', 'tracerResponse', 'employerResponses']);

        return view('alumni.show', ['alumni' => $alumni]);
    }
}
