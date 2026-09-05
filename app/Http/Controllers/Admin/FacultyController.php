<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacultyController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Faculty::class);

        return view('admin.faculties.index', [
            'faculties' => Faculty::withCount(['studyPrograms', 'alumni'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Faculty::class);

        return view('admin.faculties.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Faculty::class);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:faculties,code'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        Faculty::create($data);

        return redirect()->route('admin.faculties.index')->with('status', 'Fakultas berhasil ditambahkan.');
    }

    public function edit(Faculty $faculty): View
    {
        $this->authorize('update', $faculty);

        return view('admin.faculties.edit', ['faculty' => $faculty]);
    }

    public function update(Request $request, Faculty $faculty): RedirectResponse
    {
        $this->authorize('update', $faculty);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:faculties,code,'.$faculty->id],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $faculty->update($data);

        return redirect()->route('admin.faculties.index')->with('status', 'Fakultas berhasil diperbarui.');
    }

    public function destroy(Faculty $faculty): RedirectResponse
    {
        $this->authorize('delete', $faculty);

        $faculty->delete();

        return redirect()->route('admin.faculties.index')->with('status', 'Fakultas berhasil dihapus.');
    }
}
