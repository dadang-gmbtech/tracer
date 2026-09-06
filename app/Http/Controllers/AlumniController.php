<?php

namespace App\Http\Controllers;

use App\Exports\AlumniTemplateExport;
use App\Imports\AlumniImport;
use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function importForm(Request $request): View
    {
        Gate::authorize('fill-tracer');

        return view('alumni.import', [
            'faculties' => $this->selectableFaculties($request->user()),
        ]);
    }

    public function template(): BinaryFileResponse
    {
        Gate::authorize('fill-tracer');

        return Excel::download(new AlumniTemplateExport, 'template-data-alumni.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('fill-tracer');

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
        ]);

        $actor = $request->user();

        // Admin Fakultas/Surveyor can only ever bootstrap their own faculty.
        $facultyId = $actor->hasAnyRole(['Admin Fakultas', 'Surveyor'])
            ? $actor->faculty_id
            : ($data['faculty_id'] ?? null);

        // A large/complex real-world file can push PhpSpreadsheet's peak
        // memory past PHP's default limit while parsing it.
        ini_set('memory_limit', '2048M');
        set_time_limit(300);

        $import = new AlumniImport($actor, $facultyId ? Faculty::find($facultyId) : null);
        Excel::import($import, $request->file('file'));

        return redirect()->route('alumni.import.form')
            ->with('status', "{$import->created} alumni baru dibuat, {$import->updated} data alumni diperbarui.")
            ->with('importSkipped', $import->skipped);
    }

    /**
     * Faculties the importing user is allowed to pick as the fallback
     * faculty for rows whose program studi doesn't exist yet.
     */
    private function selectableFaculties(User $user): Collection
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin Universitas'])) {
            return Faculty::orderBy('name')->get();
        }

        if ($user->hasAnyRole(['Admin Fakultas', 'Surveyor']) && $user->faculty) {
            return collect([$user->faculty]);
        }

        return collect();
    }
}
