<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CountryTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\CountriesImport;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CountryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Country::class);

        return view('admin.countries.index', [
            'countries' => Country::withCount('provinces')->orderBy('name')->paginate(40),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Country::class);

        return view('admin.countries.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Country::class);

        Country::create($request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:countries,code'],
            'name' => ['required', 'string', 'max:255'],
        ]));

        return redirect()->route('admin.countries.index')->with('status', 'Negara berhasil ditambahkan.');
    }

    public function edit(Country $country): View
    {
        $this->authorize('update', $country);

        return view('admin.countries.edit', ['country' => $country]);
    }

    public function update(Request $request, Country $country): RedirectResponse
    {
        $this->authorize('update', $country);

        $country->update($request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('countries', 'code')->ignore($country)],
            'name' => ['required', 'string', 'max:255'],
        ]));

        return redirect()->route('admin.countries.index')->with('status', 'Negara berhasil diperbarui.');
    }

    public function destroy(Country $country): RedirectResponse
    {
        $this->authorize('delete', $country);

        $country->delete();

        return redirect()->route('admin.countries.index')->with('status', 'Negara berhasil dihapus.');
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', Country::class);

        return Excel::download(new CountryTemplateExport, 'template-negara.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Country::class);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new CountriesImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.countries.index')
            ->with('status', "{$import->created} negara baru dibuat, {$import->updated} negara diperbarui.");
    }
}
