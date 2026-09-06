<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Impor Data Tracer Studi') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">{{ session('status') }}</div>
            @endif

            @if (session('importSkipped') && count(session('importSkipped')))
                <div class="bg-amber-50 text-amber-800 text-sm rounded-md p-4">
                    <p class="font-medium mb-2">{{ count(session('importSkipped')) }} baris dilewati:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach (session('importSkipped') as $skip)
                            <li>Baris {{ $skip['row'] }}: {{ $skip['reason'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <div class="text-sm text-gray-600 space-y-2">
                    <p>
                        Susunan kolom mengikuti file <strong>Ekspor Data Tracer</strong> (kolom identitas, seluruh
                        kode pertanyaan f8–f1614, hingga kolom lokasi kerja) — kolom <code>f504</code> tidak
                        dipakai. Cara termudah: unduh data saat ini, edit/tambahkan jawaban di Excel, lalu unggah
                        kembali di sini.
                    </p>
                    <p>
                        Setiap baris dicocokkan lewat kolom <strong>nimhsmsmh</strong> (NIM). Kalau NIM belum
                        terdaftar, data alumni (beserta fakultas/prodi kalau belum ada) akan <strong>dibuat
                        otomatis</strong> dari kolom identitas di baris yang sama (kdptimsmh s.d. namaprogdikti),
                        lalu jawaban tracer-nya disimpan. Baris di luar cakupan Anda akan dilewati dan dilaporkan.
                    </p>
                    <p class="text-amber-700">
                        Catatan: alumni yang dibuat lewat impor ini <strong>belum bisa login</strong> (NIM &
                        Tanggal Lahir) karena file ini tidak membawa data tanggal lahir asli.
                    </p>
                    <a href="{{ route('export.tracer') }}" class="inline-block text-blue-600 hover:underline font-medium">
                        Unduh Data Tracer Saat Ini (sebagai template)
                    </a>
                </div>

                <form method="POST" action="{{ route('tracer.import') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="file" value="File Excel/CSV" />
                        <input id="file" type="file" name="file" class="mt-1 block w-full text-sm" required>
                        <x-input-error :messages="$errors->get('file')" class="mt-1" />
                    </div>
                    <div class="flex justify-end">
                        <x-primary-button>Impor</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
