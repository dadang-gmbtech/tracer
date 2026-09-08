<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $alumni->nama }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6 grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">NIM</span><p class="font-medium">{{ $alumni->nim }}</p></div>
                <div><span class="text-gray-500">Email</span><p class="font-medium">{{ $alumni->email }}</p></div>
                <div><span class="text-gray-500">Fakultas</span><p class="font-medium">{{ $alumni->faculty?->name }}</p></div>
                <div><span class="text-gray-500">Program Studi</span><p class="font-medium">{{ $alumni->studyProgram?->name }}</p></div>
                <div><span class="text-gray-500">Tahun Lulus</span><p class="font-medium">{{ $alumni->graduation_year }}</p></div>
                <div><span class="text-gray-500">No. Telp</span><p class="font-medium">{{ $alumni->phone }}</p></div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-800">Data Tracer Studi</h3>
                    @can('fillTracer', $alumni)
                        <a href="{{ route('tracer.edit', $alumni) }}" class="text-sm text-blue-600 hover:underline">
                            {{ $alumni->tracerResponse ? 'Edit Data' : 'Isi Data' }}
                        </a>
                    @endcan
                </div>

                @if ($alumni->tracerResponse)
                    <p class="text-sm text-gray-600">Terakhir diperbarui: {{ $alumni->tracerResponse->submitted_at?->translatedFormat('d F Y H:i') }}</p>
                @else
                    <p class="text-sm text-gray-400">Alumni ini belum mengisi kuesioner tracer studi.</p>
                @endif

                @can('view', $alumni)
                    <form method="POST" action="{{ route('tracer.share-link', $alumni) }}" class="mt-4">
                        @csrf
                        <x-secondary-button type="submit">Buat Tautan Form Pengguna Alumni</x-secondary-button>
                    </form>
                @endcan
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Penilaian Pengguna Alumni</h3>
                @if ($alumni->employerResponses)
                    <p class="text-sm text-gray-600">Diisi oleh {{ $alumni->employerResponses->nama_pengisi }} ({{ $alumni->employerResponses->jabatan }}) — {{ $alumni->employerResponses->nama_perusahaan }}</p>
                @else
                    <p class="text-sm text-gray-400">Belum ada penilaian dari pengguna alumni.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
