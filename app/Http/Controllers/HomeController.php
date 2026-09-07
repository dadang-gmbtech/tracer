<?php

namespace App\Http\Controllers;

use App\Models\HomePageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Public landing page ("halaman depan") — logged-out visitors get an
 * overview of the tracer study and how to fill it in; a logged-in visitor
 * hitting "/" is sent straight to where they'd normally land after logging
 * in instead of seeing this page.
 */
class HomeController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect(auth()->user()->postLoginUrl());
        }

        return view('home', ['content' => HomePageContent::current()]);
    }
}
