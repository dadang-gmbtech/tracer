<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Impor Data Pengguna Alumni') }}</h2>
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
                        Untuk menambahkan hasil <strong>kuesioner Pengguna Alumni</strong> yang dikumpulkan di luar
                        sistem (formulir kertas, WhatsApp, telepon). Setiap baris dicocokkan ke alumni lewat kolom
                        <strong>nim</strong> — NIM yang belum terdaftar akan dilewati, bukan dibuatkan alumni baru.
                    </p>
                    <p>
                        Kolom <code>q1_kerja_sama_tim</code> sampai <code>q7_integritas</code> diisi angka
                        <strong>1 (Sangat Baik), 2 (Baik), 3 (Cukup), 4 (Kurang)</strong> — skala yang sama dengan
                        formulir online. Satu alumni boleh punya lebih dari satu penilaian (mis. dari perusahaan yang
                        berbeda), jadi setiap baris selalu menambah data baru, bukan menimpa yang sebelumnya.
                    </p>
                    <a href="{{ route('employer.import.template') }}" class="inline-block text-blue-600 hover:underline font-medium">
                        Unduh Template Excel
                    </a>
                </div>

                <form method="POST" action="{{ route('employer.import') }}" enctype="multipart/form-data" class="space-y-4">
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
