<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProvinceTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\ProvincesImport;
use App\Models\Country;
use App\Models\Province;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProvinceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Province::class);

        return view('admin.provinces.index', [
            'provinces' => Province::withCount('cities')->with('country')->orderBy('name')->paginate(40),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Province::class);

        return view('admin.provinces.create', ['countries' => Country::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Province::class);

        Province::create($request->validate([
            'country_id' => ['nullable', 'exists:countries,id'],
            'code' => ['required', 'string', 'max:10', 'unique:provinces,code'],
            'name' => ['required', 'string', 'max:255'],
        ]));

        return redirect()->route('admin.provinces.index')->with('status', 'Provinsi berhasil ditambahkan.');
    }

    public function edit(Province $province): View
    {
        $this->authorize('update', $province);

        return view('admin.provinces.edit', ['province' => $province, 'countries' => Country::orderBy('name')->get()]);
    }

    public function update(Request $request, Province $province): RedirectResponse
    {
        $this->authorize('update', $province);

        $province->update($request->validate([
            'country_id' => ['nullable', 'exists:countries,id'],
            'code' => ['required', 'string', 'max:10', 'unique:provinces,code,'.$province->id],
            'name' => ['required', 'string', 'max:255'],
        ]));

        return redirect()->route('admin.provinces.index')->with('status', 'Provinsi berhasil diperbarui.');
    }

    public function destroy(Province $province): RedirectResponse
    {
        $this->authorize('delete', $province);

        $province->delete();

        return redirect()->route('admin.provinces.index')->with('status', 'Provinsi berhasil dihapus.');
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', Province::class);

        return Excel::download(new ProvinceTemplateExport, 'template-provinsi.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Province::class);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new ProvincesImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.provinces.index')
            ->with('status', "{$import->created} provinsi baru dibuat, {$import->updated} provinsi diperbarui.");
    }
}
