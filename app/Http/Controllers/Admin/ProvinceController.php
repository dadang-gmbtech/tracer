<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProvinceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Province::class);

        return view('admin.provinces.index', [
            'provinces' => Province::withCount('cities')->orderBy('name')->paginate(40),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Province::class);

        return view('admin.provinces.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Province::class);

        Province::create($request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:provinces,code'],
            'name' => ['required', 'string', 'max:255'],
        ]));

        return redirect()->route('admin.provinces.index')->with('status', 'Provinsi berhasil ditambahkan.');
    }

    public function edit(Province $province): View
    {
        $this->authorize('update', $province);

        return view('admin.provinces.edit', ['province' => $province]);
    }

    public function update(Request $request, Province $province): RedirectResponse
    {
        $this->authorize('update', $province);

        $province->update($request->validate([
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
}
