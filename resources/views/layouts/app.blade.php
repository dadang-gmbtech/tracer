<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @if (session()->has(\App\Http\Controllers\Admin\ImpersonationController::SESSION_KEY))
                <div style="background: #fef3c7; border-bottom: 1px solid #f59e0b; color: #78350f; padding: 0.6rem 1rem;">
                    <div style="max-width: 80rem; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem 1rem; font-size: 0.875rem;">
                        <span>
                            Anda sedang login sebagai <strong>{{ auth()->user()->name }}</strong>
                            ({{ auth()->user()->getRoleNames()->join(', ') ?: 'tanpa role' }}).
                        </span>
                        <form method="POST" action="{{ route('impersonate.stop') }}" style="margin: 0;">
                            @csrf
                            <button type="submit" style="background: #78350f; color: #fff; border: 0; border-radius: 0.375rem; padding: 0.35rem 0.85rem; font-size: 0.8125rem; font-weight: 600; cursor: pointer;">
                                Kembali ke akun Super Admin
                            </button>
                        </form>
                    </div>
                </div>
            @endif
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
