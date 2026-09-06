<?php

namespace App\Http\Controllers;

use App\Exports\AlumniTemplateExport;
use App\Imports\AlumniImport;
use App\Models\Alumni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function importForm(): View
    {
        Gate::authorize('fill-tracer');

        return view('alumni.import');
    }

    public function template(): BinaryFileResponse
    {
        Gate::authorize('fill-tracer');

        return Excel::download(new AlumniTemplateExport, 'template-data-alumni.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('fill-tracer');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new AlumniImport($request->user());
        Excel::import($import, $request->file('file'));

        return redirect()->route('alumni.import.form')
            ->with('status', "{$import->created} alumni baru dibuat, {$import->updated} data alumni diperbarui.")
            ->with('importSkipped', $import->skipped);
    }
}
