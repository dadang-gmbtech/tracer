<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengguna Alumni — {{ $alumni->nama }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="max-w-2xl mx-auto py-10 px-4">
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-gray-800">Kuesioner Pengguna Alumni</h1>
            <p class="text-sm text-gray-600">Universitas Jenderal Soedirman</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 text-red-700 text-sm rounded-md p-4 mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <h2 class="font-semibold text-gray-800 mb-3">Alumni yang Dinilai</h2>
            <div class="text-sm space-y-1">
                <p><span class="text-gray-500">NIM:</span> {{ $alumni->nim }}</p>
                <p><span class="text-gray-500">Nama:</span> {{ $alumni->nama }}</p>
                <p><span class="text-gray-500">Program Studi:</span> {{ $alumni->studyProgram?->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ url()->full() }}" class="bg-white shadow-sm rounded-lg p-6 space-y-5">
            @csrf

            <h2 class="font-semibold text-gray-800">Pengguna Alumni</h2>

            <div>
                <x-input-label for="nama_pengisi" value="Nama Lengkap" />
                <x-text-input id="nama_pengisi" name="nama_pengisi" class="mt-1 w-full" :value="old('nama_pengisi')" required />
                <x-input-error :messages="$errors->get('nama_pengisi')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="jabatan" value="Jabatan" />
                <x-text-input id="jabatan" name="jabatan" class="mt-1 w-full" :value="old('jabatan')" />
            </div>
            <div>
                <x-input-label for="nama_perusahaan" value="Nama Perusahaan" />
                <x-text-input id="nama_perusahaan" name="nama_perusahaan" class="mt-1 w-full" :value="old('nama_perusahaan')" required />
                <x-input-error :messages="$errors->get('nama_perusahaan')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="alamat_perusahaan" value="Alamat Perusahaan" />
                <x-text-input id="alamat_perusahaan" name="alamat_perusahaan" class="mt-1 w-full" :value="old('alamat_perusahaan')" />
            </div>
            <div>
                <x-input-label for="no_telp" value="No Telp Perusahaan" />
                <x-text-input id="no_telp" name="no_telp" class="mt-1 w-full" :value="old('no_telp')" />
            </div>

            <h2 class="font-semibold text-gray-800 pt-2 border-t">Kuesioner</h2>

            @foreach ([
                ['q1_kerja_sama_tim', '1. Bagaimana kemampuan Kerja Sama Tim yang dimiliki lulusan?'],
                ['q2_pengembangan_diri', '2. Bagaimana kemampuan pengembangan diri yang dimiliki lulusan?'],
                ['q3_komunikasi', '3. Bagaimana kemampuan berkomunikasi yang dimiliki lulusan?'],
                ['q4_teknologi_informasi', '4. Bagaimana kemampuan lulusan dalam penggunaan Teknologi Informasi?'],
                ['q5_bahasa_asing', '5. Bagaimana kemampuan menggunakan Bahasa Asing yang dimiliki lulusan?'],
                ['q6_keahlian', '6. Bagaimana kualitas keahlian (ilmu, skill, profesionalisme) lulusan?'],
                ['q7_integritas', '7. Bagaimana integritas (etika dan moral) yang dimiliki lulusan?'],
            ] as [$field, $label])
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">{{ $label }}</p>
                    <div class="flex gap-4 text-sm">
                        @foreach ([1 => 'Sangat Baik', 2 => 'Baik', 3 => 'Cukup', 4 => 'Kurang'] as $val => $text)
                            <label class="flex items-center gap-1">
                                <input type="radio" name="{{ $field }}" value="{{ $val }}" @checked(old($field) == $val) required>
                                {{ $text }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get($field)" class="mt-1" />
                </div>
            @endforeach

            @if ($extraQuestions->isNotEmpty())
                <h2 class="font-semibold text-gray-800 pt-2 border-t">Pertanyaan Tambahan</h2>
                @foreach ($extraQuestions as $q)
                    <div>
                        <x-input-label :value="$q->label" />
                        <x-text-input name="extra[{{ $q->id }}]" class="mt-1 w-full" />
                    </div>
                @endforeach
            @endif

            <div class="flex justify-end pt-2">
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </div>
</body>
</html>
