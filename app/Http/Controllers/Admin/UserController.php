<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SurveyorTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\SurveyorImport;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    private const STAFF_ROLES = [
        'Admin Universitas', 'Admin Fakultas', 'Admin Prodi', 'Surveyor',
        'Pimpinan Universitas', 'Pimpinan Fakultas',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $actor = $request->user();

        $query = User::query()->with(['roles', 'faculty', 'studyProgram'])->role(self::STAFF_ROLES);

        if ($actor->hasRole('Admin Fakultas')) {
            $query->where('faculty_id', $actor->faculty_id)->role(['Admin Prodi', 'Surveyor']);
        } elseif ($actor->hasRole('Admin Prodi')) {
            $query->where('program_study_id', $actor->program_study_id)->role('Surveyor');
        }

        if ($request->filled('role')) {
            $query->role($request->string('role')->value());
        }

        return view('admin.users.index', [
            'users' => $query->orderBy('name')->paginate(25)->withQueryString(),
            'roles' => $this->assignableRoles($actor),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => $this->assignableRoles($request->user()),
            'faculties' => Faculty::orderBy('name')->get(),
            'programStudies' => StudyProgram::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $this->validated($request);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('admin.users.index')->with('status', 'User berhasil ditambahkan.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'targetUser' => $user,
            'roles' => $this->assignableRoles($request->user()),
            'faculties' => Faculty::orderBy('name')->get(),
            'programStudies' => StudyProgram::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $this->validated($request, $user);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'no_telp' => $data['no_telp'] ?? null,
            'faculty_id' => $data['faculty_id'] ?? null,
            'program_study_id' => $data['program_study_id'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles([$data['role']]);

        return redirect()->route('admin.users.index')->with('status', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $ids = (array) $request->input('ids', []);
        $actor = $request->user();
        $deleted = 0;

        foreach (User::whereIn('id', $ids)->get() as $user) {
            if ($actor->can('delete', $user)) {
                $user->delete();
                $deleted++;
            }
        }

        return redirect()->route('admin.users.index')->with('status', "{$deleted} user berhasil dihapus.");
    }

    public function importForm(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.import');
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', User::class);

        return Excel::download(new SurveyorTemplateExport, 'template-surveyor.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $actor = $request->user();
        $forcedFacultyId = $actor->hasRole('Admin Fakultas') ? $actor->faculty_id : null;

        $import = new SurveyorImport($forcedFacultyId);
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.users.index')
            ->with('status', count($import->created).' surveyor berhasil diimpor.')
            ->with('importedCredentials', $import->created);
    }

    private function assignableRoles(User $actor): array
    {
        return match (true) {
            $actor->hasAnyRole(['Super Admin', 'Admin Universitas']) => self::STAFF_ROLES,
            $actor->hasRole('Admin Fakultas') => ['Admin Prodi', 'Surveyor'],
            $actor->hasRole('Admin Prodi') => ['Surveyor'],
            default => [],
        };
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $actor = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            // A string rule built as 'unique:users,email,'.($user?->id) leaves a
            // trailing empty "ignore id" segment when $user is null (create),
            // which Postgres rejects outright when comparing it to the bigint id
            // column (SQLSTATE 22P02) — MySQL/SQLite silently tolerate it, so this
            // only broke in production. Rule::unique()->ignore(null) is a no-op.
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'no_telp' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'string', 'in:'.implode(',', $this->assignableRoles($actor))],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
            'program_study_id' => ['nullable', 'exists:program_studies,id'],
            'password' => [$user ? 'nullable' : 'required', 'nullable', 'string', 'min:8'],
        ];

        $data = $request->validate($rules);

        // Non-university admins can only create/edit users within their own scope.
        if ($actor->hasRole('Admin Fakultas')) {
            $data['faculty_id'] = $actor->faculty_id;
        }

        if ($actor->hasRole('Admin Prodi')) {
            $data['faculty_id'] = $actor->faculty_id;
            $data['program_study_id'] = $actor->program_study_id;
        }

        return $data;
    }
}
