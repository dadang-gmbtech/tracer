<?php

namespace App\Http\Controllers;

use App\Exports\EmployerResponseTemplateExport;
use App\Imports\EmployerResponseImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Bulk-add "Pengguna Alumni" (employer) feedback collected offline. Kept
 * separate from EmployerResponseController, which is the public/no-login
 * controller for the signed-URL form — this one requires a logged-in admin.
 */
class EmployerImportController extends Controller
{
    public function importForm(): View
    {
        Gate::authorize('import-employer-data');

        return view('employer.import');
    }

    public function template(): BinaryFileResponse
    {
        Gate::authorize('import-employer-data');

        return Excel::download(new EmployerResponseTemplateExport, 'template-pengguna-alumni.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('import-employer-data');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        // A large/complex real-world file can push PhpSpreadsheet's peak
        // memory past PHP's default limit while parsing it.
        ini_set('memory_limit', '2048M');
        set_time_limit(300);

        $import = new EmployerResponseImport($request->user());
        Excel::import($import, $request->file('file'));

        return redirect()->route('employer.import.form')
            ->with('status', "{$import->created} data pengguna alumni berhasil disimpan, {$import->noFeedback} baris tanpa data pengguna alumni dilewati.")
            ->with('importSkipped', $import->skipped);
    }
}
