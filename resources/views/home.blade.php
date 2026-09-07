<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tracer Studi — Universitas Jenderal Soedirman</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased">

    <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <a href="#beranda" class="flex items-center gap-2">
                <x-application-logo class="h-8 w-auto" />
                <span class="font-semibold text-gray-800">Tracer Studi UNSOED</span>
            </a>
            <nav class="hidden sm:flex items-center gap-6 text-sm text-gray-600">
                <a href="#tentang" class="hover:text-blue-700 transition-colors">Tentang</a>
                <a href="#alur" class="hover:text-blue-700 transition-colors">Alur Pengisian</a>
            </nav>
            <a href="{{ route('login') }}"
               class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 transition-colors shadow-sm hover:shadow">
                Login
            </a>
        </div>
    </header>

    <section id="beranda" class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-blue-800 to-indigo-900 text-white">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-500/30 rounded-full blur-3xl animate-blob"></div>
            <div class="absolute top-1/3 -right-24 w-96 h-96 bg-indigo-400/20 rounded-full blur-3xl animate-blob" style="animation-delay: 4s"></div>
            <div class="absolute -bottom-24 left-1/3 w-96 h-96 bg-sky-400/20 rounded-full blur-3xl animate-blob" style="animation-delay: 8s"></div>
        </div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 text-center">
            <div class="animate-fade-in-up">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium bg-white/10 text-blue-50 border border-white/20">
                    Survei Alumni Resmi
                </span>
                <h1 class="mt-4 text-3xl sm:text-5xl font-bold tracking-tight">{{ $content->hero_title }}</h1>
                <p class="mt-2 text-blue-100 text-lg">{{ $content->hero_subtitle }}</p>
                <p class="mt-6 max-w-2xl mx-auto text-blue-50/90">
                    {{ $content->hero_description }}
                </p>
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 mt-8 px-8 py-3 rounded-md text-base font-semibold text-blue-700 bg-white hover:bg-blue-50 transition-all hover:-translate-y-0.5 shadow-lg hover:shadow-xl">
                    Isi Kuesioner Sekarang
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
        </div>

        <svg class="relative block w-full text-gray-50" viewBox="0 0 1440 48" fill="currentColor" preserveAspectRatio="none" style="height: 48px">
            <path d="M0,32 C240,64 480,0 720,16 C960,32 1200,64 1440,32 L1440,48 L0,48 Z"></path>
        </svg>
    </section>

    <section id="tentang" class="bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div x-data="revealOnScroll" :class="visible && 'opacity-100 translate-y-0'"
                 class="text-center opacity-0 translate-y-6 transition-all duration-700">
                <h2 class="text-2xl font-bold text-gray-800">Tentang Tracer Studi</h2>
                <p class="mt-4 max-w-3xl mx-auto text-gray-600">
                    {{ $content->tentang_description }}
                </p>
            </div>

            @php
                // Icons are fixed to each card's position — only the wording (title/description)
                // is admin-editable, via HomePageContent::current()->tentang_cards.
                $tentangIcons = [
                    'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
                    'M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z',
                    'M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0',
                ];
            @endphp

            <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-6">
                @foreach ($content->tentang_cards as $i => $card)
                    <div x-data="revealOnScroll" :class="visible && 'opacity-100 translate-y-0'"
                         style="transition-delay: {{ $i * 100 }}ms"
                         class="opacity-0 translate-y-6 transition-all duration-700 bg-white shadow-sm rounded-lg p-6 text-center hover:shadow-md hover:-translate-y-1 transition-transform">
                        <div class="mx-auto w-12 h-12 flex items-center justify-center rounded-full bg-blue-50 text-blue-700">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tentangIcons[$i] ?? '' }}" />
                            </svg>
                        </div>
                        <div class="mt-4 text-blue-700 font-semibold">{{ $card['title'] }}</div>
                        <p class="mt-2 text-sm text-gray-600">{{ $card['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="alur">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div x-data="revealOnScroll" :class="visible && 'opacity-100 translate-y-0'"
                 class="text-center opacity-0 translate-y-6 transition-all duration-700">
                <h2 class="text-2xl font-bold text-gray-800">Langkah Mudah Mengisi Kuesioner</h2>
            </div>

            <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 relative">
                <div class="hidden lg:block absolute top-9 left-0 right-0 h-0.5 bg-gradient-to-r from-blue-200 via-blue-400 to-blue-200"></div>

                @foreach ($content->alur_steps as $i => $step)
                    <div x-data="revealOnScroll" :class="visible && 'opacity-100 translate-y-0'"
                         style="transition-delay: {{ $i * 120 }}ms"
                         class="relative opacity-0 translate-y-6 transition-all duration-700 bg-white shadow-sm rounded-lg p-6 text-center hover:shadow-md hover:-translate-y-1 transition-transform">
                        <div class="relative z-10 mx-auto w-9 h-9 flex items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-blue-800 text-white font-semibold shadow-md ring-4 ring-white">
                            {{ $i + 1 }}
                        </div>
                        <p class="mt-3 text-sm text-gray-600">{{ $step }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <footer class="bg-gray-800 text-gray-300">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center text-sm">
            <p class="font-medium text-white">Universitas Jenderal Soedirman</p>
            <p class="mt-1">{{ $content->footer_address }}</p>
            <p class="mt-4 text-gray-400">&copy; {{ now()->year }} Sistem Tracer Studi UNSOED.</p>
        </div>
    </footer>

</body>
</html>
