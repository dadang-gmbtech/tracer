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
                        dari sistem akademik (kolom seperti <code>nim, nama, tahunlulus, emailunsoed, emailpersonal,
                        npwp, tgllahir, notelp, kodeprog, namajenjang, namaprogdikti</code>) atau template di bawah.
                    </p>
                    <p>
                        Setiap baris dicocokkan lewat kolom <strong>nim</strong>, dan program studi dicocokkan lewat
                        <strong>kodeprog</strong>. Kalau kode prodi belum terdaftar, sistem butuh tahu fakultasnya —
                        urutan yang dicoba: kolom <code>kode_fakultas</code> / <code>nama_fakultas</code> di file
                        (kalau ada), lalu dropdown <strong>Fakultas</strong> di bawah, dan terakhir ditebak otomatis
                        dari huruf pertama NIM (mis. NIM diawali "H" → Fakultas Teknik) kalau cocok dengan fakultas
                        yang sudah terdaftar.
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

                    @if ($faculties->count() > 1)
                        <div>
                            <x-input-label for="faculty_id" value="Fakultas (untuk prodi baru di file ini)" />
                            <select id="faculty_id" name="faculty_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">— Otomatis dari file (kode_fakultas), kalau ada —</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty->id }}" @selected(old('faculty_id') == $faculty->id)>{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('faculty_id')" class="mt-1" />
                        </div>
                    @elseif ($faculties->count() === 1)
                        <div>
                            <x-input-label value="Fakultas" />
                            <p class="mt-1 text-sm text-gray-700">{{ $faculties->first()->name }} (sesuai cakupan Anda)</p>
                        </div>
                    @endif

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
