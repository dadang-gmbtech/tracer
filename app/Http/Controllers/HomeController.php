<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\HomePageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Public landing page ("halaman depan") — logged-out visitors get an
 * overview of the tracer study, how to fill it in, and aggregate response
 * statistics; a logged-in visitor hitting "/" is sent straight to where
 * they'd normally land after logging in instead of seeing this page.
 */
class HomeController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect(auth()->user()->postLoginUrl());
        }

        $totalAlumni = Alumni::count();
        $totalResponden = Alumni::whereHas('tracerResponse')->count();

        $perFakultas = Faculty::withCount([
            'alumni',
            'alumni as responden_count' => fn ($query) => $query->whereHas('tracerResponse'),
        ])->orderBy('name')->get();

        return view('home', [
            'content' => HomePageContent::current(),
            'totalAlumni' => $totalAlumni,
            'totalResponden' => $totalResponden,
            'tingkatRespon' => $totalAlumni > 0 ? round($totalResponden / $totalAlumni * 100, 1) : 0.0,
            'perFakultas' => $perFakultas,
        ]);
    }
}
