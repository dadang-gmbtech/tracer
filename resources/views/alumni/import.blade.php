<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Impor Data Alumni') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">{{ session('status') }}</div>
            @endif

            @if (session('importSkipped') && count(session('importSkipped')))
                <div class="bg-amber-50 text-amber-800 text-sm rounded-md p-4">
                    <p class="font-medium mb-2">{{ count(session('importSkipped')) }} baris dilewati:</p>
                    <ul class="list-disc list-inside space-y-0.5 max-h-64 overflow-y-auto">
                        @foreach (session('importSkipped') as $skip)
                            <li>Baris {{ $skip['row'] }}: {{ $skip['reason'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <div class="text-sm text-gray-600 space-y-2">
                    <p>
                        Untuk mengisi/memperbarui <strong>data identitas alumni</strong> (bukan jawaban tracer studi
                        — untuk itu pakai menu <strong>Impor Data Tracer</strong>). Bisa langsung pakai file ekspor
                        dari sistem akademik (kolom seperti <code>nim, nama, tahunlulu, emailunsoed, emailpersonal,
                        npwp, tgllahir, notelp, kodeprog, namajenjang, namaprogdikti</code>) atau template di bawah.
                    </p>
                    <p>
                        Setiap baris dicocokkan lewat kolom <strong>nim</strong>, dan program studi dicocokkan lewat
                        <strong>kodeprog</strong> — program studi tersebut harus sudah terdaftar (menu Administrasi
                        &gt; Program Studi), kecuali file juga menyertakan kolom <code>kode_fakultas</code> /
                        <code>nama_fakultas</code> untuk membuatkannya otomatis.
                    </p>
                    <p class="text-amber-700">
                        Kalau kolom <strong>tgllahir</strong> (tanggal lahir) terisi, akun login alumni (NIM &
                        Tanggal Lahir) otomatis dibuat/diperbarui. Kosongkan kolom itu kalau belum punya data
                        tanggal lahir yang valid.
                    </p>
                    <a href="{{ route('alumni.import.template') }}" class="inline-block text-blue-600 hover:underline font-medium">
                        Unduh Template Excel
                    </a>
                </div>

                <form method="POST" action="{{ route('alumni.import') }}" enctype="multipart/form-data" class="space-y-4">
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
