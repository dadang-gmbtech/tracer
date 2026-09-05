<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CityTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\CitiesImport;
use App\Models\City;
use App\Models\Province;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CityController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', City::class);

        return view('admin.cities.index', [
            'cities' => City::with('province')->orderBy('name')->paginate(40),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', City::class);

        return view('admin.cities.create', ['provinces' => Province::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', City::class);

        City::create($request->validate([
            'province_id' => ['required', 'exists:provinces,id'],
            'code' => ['nullable', 'string', 'max:20', 'unique:cities,code'],
            'name' => ['required', 'string', 'max:255'],
        ]));

        return redirect()->route('admin.cities.index')->with('status', 'Kabupaten/kota berhasil ditambahkan.');
    }

    public function edit(City $city): View
    {
        $this->authorize('update', $city);

        return view('admin.cities.edit', ['city' => $city, 'provinces' => Province::orderBy('name')->get()]);
    }

    public function update(Request $request, City $city): RedirectResponse
    {
        $this->authorize('update', $city);

        $city->update($request->validate([
            'province_id' => ['required', 'exists:provinces,id'],
            'code' => ['nullable', 'string', 'max:20', 'unique:cities,code,'.$city->id],
            'name' => ['required', 'string', 'max:255'],
        ]));

        return redirect()->route('admin.cities.index')->with('status', 'Kabupaten/kota berhasil diperbarui.');
    }

    public function destroy(City $city): RedirectResponse
    {
        $this->authorize('delete', $city);

        $city->delete();

        return redirect()->route('admin.cities.index')->with('status', 'Kabupaten/kota berhasil dihapus.');
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', City::class);

        return Excel::download(new CityTemplateExport, 'template-kabupaten-kota.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', City::class);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new CitiesImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.cities.index')
            ->with('status', "{$import->imported} kabupaten/kota berhasil diimpor.");
    }
}
