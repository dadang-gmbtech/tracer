<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UmpTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\UmpImport;
use App\Models\Province;
use App\Models\UmpSalary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UmpController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', UmpSalary::class);

        return view('admin.ump.index', [
            'wages' => UmpSalary::with('province')->orderByDesc('year')->orderBy('province_id')->paginate(40),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', UmpSalary::class);

        return view('admin.ump.create', ['provinces' => Province::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', UmpSalary::class);

        UmpSalary::updateOrCreate(
            ['province_id' => $request->input('province_id'), 'year' => $request->input('year')],
            $request->validate([
                'province_id' => ['required', 'exists:provinces,id'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'amount' => ['required', 'numeric', 'min:0'],
            ])
        );

        return redirect()->route('admin.ump.index')->with('status', 'UMP berhasil disimpan.');
    }

    public function edit(UmpSalary $ump): View
    {
        $this->authorize('update', $ump);

        return view('admin.ump.edit', ['ump' => $ump, 'provinces' => Province::orderBy('name')->get()]);
    }

    public function update(Request $request, UmpSalary $ump): RedirectResponse
    {
        $this->authorize('update', $ump);

        $ump->update($request->validate([
            'province_id' => ['required', 'exists:provinces,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]));

        return redirect()->route('admin.ump.index')->with('status', 'UMP berhasil diperbarui.');
    }

    public function destroy(UmpSalary $ump): RedirectResponse
    {
        $this->authorize('delete', $ump);

        $ump->delete();

        return redirect()->route('admin.ump.index')->with('status', 'UMP berhasil dihapus.');
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', UmpSalary::class);

        return Excel::download(new UmpTemplateExport, 'template-ump.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', UmpSalary::class);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new UmpImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.ump.index')->with('status', "{$import->imported} data UMP berhasil diimpor.");
    }
}
