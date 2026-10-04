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
                        Susunan kolom mengikuti template resmi <strong>Pelaporan Tracer Study</strong> (Kode PT,
                        Kode Prodi, NIM/Nomor Mhs, ..., F8 sampai F37) — file yang sama bisa diunggah ke sistem
                        pelaporan nasional atau diedit lalu diunggah ulang di sini.
                    </p>
                    <p>
                        Untuk mengisi data baru, unduh <strong>Template Excel</strong> di bawah (cuma header kolom +
                        satu baris contoh). Untuk mengedit jawaban yang sudah ada, gunakan menu
                        <strong>Data Tracer → Ekspor Data Tracer</strong> — file itu berisi seluruh data yang sudah
                        tersimpan, jadi bisa besar dan lama kalau alumninya sudah banyak.
                    </p>
                    <p>
                        Setiap baris dicocokkan lewat kolom <strong>NIM/Nomor Mhs</strong>. Kalau NIM belum
                        terdaftar, data alumni akan <strong>dibuat otomatis</strong> dari kolom identitas di baris
                        yang sama — tapi kolom <strong>Kode Prodi</strong>-nya harus sudah terdaftar sebagai master
                        data Program Studi (nama/jenjang/fakultasnya diambil dari situ, bukan dari file). Baris
                        dengan kode prodi yang tidak ditemukan, atau di luar cakupan Anda, akan dilewati dan
                        dilaporkan.
                    </p>
                    <p class="text-amber-700">
                        Catatan: alumni yang dibuat lewat impor ini <strong>belum bisa login</strong> (NIM &
                        Tanggal Lahir) karena file ini tidak membawa data tanggal lahir asli.
                    </p>
                    <a href="{{ route('tracer.import.template') }}" class="inline-block text-blue-600 hover:underline font-medium">
                        Unduh Template Excel
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
