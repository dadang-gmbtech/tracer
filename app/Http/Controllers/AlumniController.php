<?php

namespace App\Http\Controllers;

use App\Exports\AlumniTemplateExport;
use App\Imports\AlumniImport;
use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AlumniController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Alumni::class);

        $user = $request->user();
        $query = Alumni::query()->visibleTo($user)->with(['faculty', 'studyProgram', 'tracerResponse']);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%");
            });
        }

        if ($request->filled('graduation_year')) {
            $query->where('graduation_year', $request->input('graduation_year'));
        }

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->integer('faculty_id'));
        }

        if ($jenjang = $request->string('jenjang')->trim()->value()) {
            $query->whereHas('studyProgram', fn ($q) => $q->where('level', $jenjang));
        }

        if ($status = $request->string('status')->trim()->value()) {
            $status === 'sudah' ? $query->whereHas('tracerResponse') : $query->whereDoesntHave('tracerResponse');
        }

        $isUniversityScoped = $user->hasAnyRole(['Super Admin', 'Admin Universitas', 'Pimpinan Universitas']);

        return view('alumni.index', [
            'alumni' => $query->orderBy('nama')->paginate(20)->withQueryString(),
            'graduationYears' => Alumni::visibleTo($user)->distinct()->orderByDesc('graduation_year')->pluck('graduation_year'),
            'faculties' => $isUniversityScoped ? Faculty::orderBy('name')->get() : collect(),
            // Bio data (NIM, nama, fakultas/prodi, ...) is master data — only
            // Super Admin may add/edit it (see AlumniPolicy). Filling in the
            // tracer questionnaire on an alumnus's behalf is a separate,
            // much wider ability (Admin/Surveyor within their own faculty).
            'canManageAlumni' => $user->can('create', Alumni::class),
            'canFillTracer' => $user->can('fill-tracer') && ! $user->hasAnyRole(['Pimpinan Universitas', 'Pimpinan Fakultas']),
        ]);
    }

    public function show(Request $request, Alumni $alumni): View
    {
        $this->authorize('view', $alumni);

        $alumni->load(['faculty', 'studyProgram', 'tracerResponse', 'employerResponses']);

        return view('alumni.show', ['alumni' => $alumni]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Alumni::class);

        // Only Super Admin ever reaches here (see AlumniPolicy), so unlike
        // importForm() below there's no per-faculty scoping to apply.
        return view('alumni.create', [
            'faculties' => Faculty::orderBy('name')->get(),
            'programStudies' => StudyProgram::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Alumni::class);

        Alumni::create($this->validated($request));

        return redirect()->route('alumni.index')->with('status', 'Alumni berhasil ditambahkan.');
    }

    public function edit(Request $request, Alumni $alumni): View
    {
        $this->authorize('update', $alumni);

        return view('alumni.edit', [
            'alumni' => $alumni,
            'faculties' => Faculty::orderBy('name')->get(),
            'programStudies' => StudyProgram::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Alumni $alumni): RedirectResponse
    {
        $this->authorize('update', $alumni);

        $alumni->update($this->validated($request, $alumni));

        return redirect()->route('alumni.index')->with('status', 'Data alumni berhasil diperbarui.');
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

    /**
     * @return array{nim: string, nama: string, email: ?string, phone: ?string, nik: ?string, npwp: ?string, faculty_id: int, program_study_id: int, graduation_year: int}
     */
    private function validated(Request $request, ?Alumni $alumni = null): array
    {
        $data = $request->validate([
            'nim' => ['required', 'string', 'max:30', Rule::unique('alumni', 'nim')->ignore($alumni)],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'nik' => ['nullable', 'string', 'max:30'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'program_study_id' => ['required', 'exists:program_studies,id'],
            'graduation_year' => ['required', 'integer', 'digits:4', 'min:1960', 'max:'.(now()->year + 1)],
        ]);

        if (StudyProgram::find($data['program_study_id'])?->faculty_id !== (int) $data['faculty_id']) {
            throw ValidationException::withMessages(['program_study_id' => 'Program studi tidak sesuai dengan fakultas yang dipilih.']);
        }

        return $data;
    }
}
