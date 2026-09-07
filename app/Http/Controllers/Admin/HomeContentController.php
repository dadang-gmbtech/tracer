<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomePageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Lets Super Admin edit the wording on the public landing page (see
 * resources/views/home.blade.php) without touching code — a singleton
 * record (HomePageContent::current()), so this is edit/update only, no
 * index/create/destroy.
 */
class HomeContentController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-home-content');

        return view('admin.home-content.edit', ['content' => HomePageContent::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('manage-home-content');

        $data = $request->validate([
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_subtitle' => ['required', 'string', 'max:255'],
            'hero_description' => ['required', 'string', 'max:2000'],
            'tentang_description' => ['required', 'string', 'max:2000'],
            'tentang_cards' => ['required', 'array', 'size:3'],
            'tentang_cards.*.title' => ['required', 'string', 'max:255'],
            'tentang_cards.*.description' => ['required', 'string', 'max:500'],
            'alur_steps' => ['required', 'array', 'size:4'],
            'alur_steps.*' => ['required', 'string', 'max:255'],
            'footer_address' => ['required', 'string', 'max:500'],
        ]);

        HomePageContent::current()->update($data);

        return redirect()->route('admin.home-content.edit')->with('status', 'Konten halaman depan berhasil disimpan.');
    }
}
