<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\SsoLoginServiceContract;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function loginSso(SsoLoginServiceContract $sso): RedirectResponse
    {
        $user = $sso->login();
        Auth::login($user);

        return redirect()->intended($user->postLoginUrl());
    }

    public function loginNim(Request $request): RedirectResponse
    {
        $request->validate([
            'nim' => ['required', 'string'],
            'tanggal_lahir' => ['required', 'date'],
        ]);

        $throttleKey = 'login-nim:'.strtolower($request->input('nim')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'nim' => 'Terlalu banyak percobaan. Coba lagi dalam beberapa menit.',
            ]);
        }

        $user = User::where('nim', $request->input('nim'))
            ->whereDate('tanggal_lahir', $request->input('tanggal_lahir'))
            ->first();

        if (! $user) {
            RateLimiter::hit($throttleKey);

            return back()->withErrors([
                'nim' => 'Kombinasi NIM dan Tanggal Lahir tidak ditemukan.',
            ])->onlyInput('nim');
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user);

        return redirect()->intended($user->postLoginUrl());
    }
}
