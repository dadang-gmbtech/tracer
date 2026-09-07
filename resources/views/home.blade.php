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

    <header class="sticky top-0 z-10 bg-white border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <a href="#beranda" class="flex items-center gap-2">
                <x-application-logo class="h-8 w-auto fill-current text-blue-700" />
                <span class="font-semibold text-gray-800">Tracer Studi UNSOED</span>
            </a>
            <nav class="hidden sm:flex items-center gap-6 text-sm text-gray-600">
                <a href="#tentang" class="hover:text-blue-700">Tentang</a>
                <a href="#alur" class="hover:text-blue-700">Alur Pengisian</a>
                <a href="#statistik" class="hover:text-blue-700">Statistik</a>
            </nav>
            <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-700 hover:bg-blue-800">
                Login
            </a>
        </div>
    </header>

    <section id="beranda" class="bg-blue-700 text-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
            <h1 class="text-3xl sm:text-4xl font-bold">Sistem Tracer Studi</h1>
            <p class="mt-2 text-blue-100 text-lg">Universitas Jenderal Soedirman</p>
            <p class="mt-6 max-w-2xl mx-auto text-blue-50">
                Kami mengundang seluruh alumni untuk mengisi kuesioner tracer studi. Masukan Anda membantu
                universitas mengevaluasi capaian pembelajaran, menjaga relevansi kurikulum dengan dunia kerja, dan
                mendukung proses akreditasi program studi.
            </p>
            <a href="{{ route('login') }}"
               class="inline-block mt-8 px-8 py-3 rounded-md text-base font-semibold text-blue-700 bg-white hover:bg-blue-50">
                Isi Kuesioner Sekarang
            </a>
        </div>
    </section>

    <section id="tentang" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h2 class="text-2xl font-bold text-gray-800 text-center">Tentang Tracer Studi</h2>
        <p class="mt-4 max-w-3xl mx-auto text-center text-gray-600">
            Tracer studi adalah penelusuran terhadap alumni untuk mengetahui perjalanan karier mereka setelah lulus —
            masa tunggu kerja, kesesuaian bidang kerja dengan program studi, hingga penilaian pengguna lulusan.
            Data ini menjadi bahan evaluasi mutu pendidikan dan salah satu syarat akreditasi institusi maupun
            program studi.
        </p>
        <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-white shadow-sm rounded-lg p-6 text-center">
                <div class="text-blue-700 font-semibold">Evaluasi Kurikulum</div>
                <p class="mt-2 text-sm text-gray-600">Menilai relevansi materi perkuliahan dengan kebutuhan dunia kerja.</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-6 text-center">
                <div class="text-blue-700 font-semibold">Penjaminan Mutu</div>
                <p class="mt-2 text-sm text-gray-600">Menjadi indikator capaian pembelajaran dan mutu lulusan.</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-6 text-center">
                <div class="text-blue-700 font-semibold">Akreditasi</div>
                <p class="mt-2 text-sm text-gray-600">Mendukung data wajib akreditasi institusi dan program studi.</p>
            </div>
        </div>
    </section>

    <section id="alur" class="bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h2 class="text-2xl font-bold text-gray-800 text-center">Langkah Mudah Mengisi Kuesioner</h2>
            <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ([
                    'Klik tombol Login di halaman ini.',
                    'Masuk memakai akun SSO UNSOED, atau NIM & Tanggal Lahir.',
                    'Isi seluruh pertanyaan pada formulir tracer studi.',
                    'Kirim jawaban Anda — data otomatis tersimpan.',
                ] as $i => $step)
                    <div class="bg-white shadow-sm rounded-lg p-6 text-center">
                        <div class="mx-auto w-9 h-9 flex items-center justify-center rounded-full bg-blue-700 text-white font-semibold">
                            {{ $i + 1 }}
                        </div>
                        <p class="mt-3 text-sm text-gray-600">{{ $step }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="statistik" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h2 class="text-2xl font-bold text-gray-800 text-center">Statistik Partisipasi Alumni</h2>

        <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-blue-50 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-700">{{ number_format($totalAlumni, 0, ',', '.') }}</div>
                <div class="mt-1 text-sm text-gray-600">Alumni Terdaftar</div>
            </div>
            <div class="bg-blue-50 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-700">{{ number_format($totalResponden, 0, ',', '.') }}</div>
                <div class="mt-1 text-sm text-gray-600">Responden Tracer Studi</div>
            </div>
            <div class="bg-blue-50 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-700">{{ $tingkatRespon }}%</div>
                <div class="mt-1 text-sm text-gray-600">Tingkat Respon Keseluruhan</div>
            </div>
        </div>

        @if ($perFakultas->isNotEmpty())
            <div class="mt-10 bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Fakultas</th>
                                <th class="px-4 py-3">Jumlah Alumni</th>
                                <th class="px-4 py-3">Responden</th>
                                <th class="px-4 py-3">Tingkat Respon</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($perFakultas as $faculty)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $faculty->name }}</td>
                                    <td class="px-4 py-3">{{ $faculty->alumni_count }}</td>
                                    <td class="px-4 py-3">{{ $faculty->responden_count }}</td>
                                    <td class="px-4 py-3">
                                        {{ $faculty->alumni_count > 0 ? round($faculty->responden_count / $faculty->alumni_count * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>

    <footer class="bg-gray-800 text-gray-300">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center text-sm">
            <p class="font-medium text-white">Universitas Jenderal Soedirman</p>
            <p class="mt-1">Jl. Dr. Suparno, Karangwangkal, Purwokerto Utara, Banyumas, Jawa Tengah</p>
            <p class="mt-4 text-gray-400">&copy; {{ now()->year }} Sistem Tracer Studi UNSOED.</p>
        </div>
    </footer>

</body>
</html>
