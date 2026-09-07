<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramStudyController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', StudyProgram::class);

        return view('admin.program-studies.index', [
            'programStudies' => StudyProgram::with('faculty')->withCount('alumni')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', StudyProgram::class);

        return view('admin.program-studies.create', ['faculties' => Faculty::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StudyProgram::class);

        $data = $this->validated($request);

        StudyProgram::create($data);

        return redirect()->route('admin.program-studies.index')->with('status', 'Program studi berhasil ditambahkan.');
    }

    public function edit(StudyProgram $programStudy): View
    {
        $this->authorize('update', $programStudy);

        return view('admin.program-studies.edit', [
            'programStudy' => $programStudy,
            'faculties' => Faculty::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, StudyProgram $programStudy): RedirectResponse
    {
        $this->authorize('update', $programStudy);

        $data = $this->validated($request, $programStudy);

        $programStudy->update($data);

        return redirect()->route('admin.program-studies.index')->with('status', 'Program studi berhasil diperbarui.');
    }

    public function destroy(StudyProgram $programStudy): RedirectResponse
    {
        $this->authorize('delete', $programStudy);

        $programStudy->delete();

        return redirect()->route('admin.program-studies.index')->with('status', 'Program studi berhasil dihapus.');
    }

    private function validated(Request $request, ?StudyProgram $programStudy = null): array
    {
        return $request->validate([
            'faculty_id' => ['required', 'exists:faculties,id'],
            // See UserController::validated() for why this must be the fluent
            // Rule::unique()->ignore() form, not a string-concatenated rule.
            'code' => ['required', 'string', 'max:20', Rule::unique('program_studies', 'code')->ignore($programStudy)],
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', 'max:20'],
        ]);
    }
}
