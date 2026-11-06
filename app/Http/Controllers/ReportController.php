<?php

namespace App\Http\Controllers;

use App\Models\HomePageContent;
use App\Models\Report;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasRole('Alumni')) {
            return redirect($request->user()->postLoginUrl());
        }

        return view('reports.index', [
            'reports' => Report::query()->visibleTo($request->user())->with(['faculty', 'studyProgram', 'uploader'])
                ->orderByDesc('year')->orderByDesc('created_at')->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Report::class);

        return view('reports.create', ['user' => $request->user()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Report::class);

        $user = $request->user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:10240'],
        ]);

        $level = match (true) {
            $user->hasAnyRole(['Super Admin', 'Admin Universitas']) => Report::LEVEL_UNIVERSITAS,
            $user->hasRole('Admin Fakultas') => Report::LEVEL_FAKULTAS,
            $user->hasRole('Admin Prodi') => Report::LEVEL_PRODI,
            default => throw new AuthorizationException,
        };

        $path = $request->file('file')->store('laporan-tracer', 'public');

        Report::create([
            'level' => $level,
            'faculty_id' => $level !== Report::LEVEL_UNIVERSITAS ? $user->faculty_id : null,
            'program_study_id' => $level === Report::LEVEL_PRODI ? $user->program_study_id : null,
            'title' => $data['title'],
            'file_path' => $path,
            'year' => $data['year'],
            'uploaded_by' => $user->id,
        ]);

        return redirect()->route('reports.index')->with('status', 'Laporan berhasil diunggah.');
    }

    public function download(Report $report): StreamedResponse
    {
        $this->authorize('view', $report);

        return Storage::disk('public')->download($report->file_path, $report->downloadFilename());
    }

    /**
     * Public download from the landing page — no login, so it's limited to
     * exactly what the page lists: university-level reports, and only while
     * the "Laporan Tracer Studi" section is switched on.
     */
    public function publicDownload(Report $report): StreamedResponse
    {
        abort_unless(HomePageContent::current()->publicReports()->contains($report), 404);

        return Storage::disk('public')->download($report->file_path, $report->downloadFilename());
    }

    public function destroy(Report $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        Storage::disk('public')->delete($report->file_path);
        $report->delete();

        return redirect()->route('reports.index')->with('status', 'Laporan berhasil dihapus.');
    }
}
