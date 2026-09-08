<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Data Alumni') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">{{ session('status') }}</div>
            @endif

            <form method="GET" class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap gap-4 items-end">
                <div>
                    <x-input-label for="q" value="Cari Nama/NIM" />
                    <x-text-input id="q" name="q" class="mt-1" placeholder="Nama atau NIM..." :value="request('q')" />
                </div>
                <div>
                    <x-input-label for="graduation_year" value="Tahun Lulus" />
                    <select id="graduation_year" name="graduation_year" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Semua</option>
                        @foreach ($graduationYears as $year)
                            <option value="{{ $year }}" @selected(request('graduation_year') == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="jenjang" value="Jenjang" />
                    <select id="jenjang" name="jenjang" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Semua</option>
                        @foreach (['D3', 'S1', 'S2', 'S3'] as $level)
                            <option value="{{ $level }}" @selected(request('jenjang') === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($faculties->isNotEmpty())
                    <div>
                        <x-input-label for="faculty_id" value="Fakultas" />
                        <select id="faculty_id" name="faculty_id" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Semua</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}" @selected(request('faculty_id') == $faculty->id)>{{ $faculty->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Semua</option>
                        <option value="sudah" @selected(request('status') === 'sudah')>Sudah Mengisi</option>
                        <option value="belum" @selected(request('status') === 'belum')>Belum Mengisi</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-primary-button>Filter</x-primary-button>
                    <a href="{{ route('alumni.index') }}"
                       class="inline-flex items-center justify-center px-3 py-2 rounded-md border border-gray-300 text-gray-500 hover:bg-gray-50"
                       title="Reset filter">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </form>

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-gray-800">Daftar Alumni</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                        {{ number_format($alumni->total(), 0, ',', '.') }}
                    </span>
                </div>
                @if ($canManageAlumni)
                    <a href="{{ route('alumni.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        + Tambah Alumni
                    </a>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3">NIM</th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Fakultas</th>
                                <th class="px-4 py-3">Prodi</th>
                                <th class="px-4 py-3">Jenjang</th>
                                <th class="px-4 py-3">Tahun Lulus</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($alumni as $a)
                                <tr>
                                    <td class="px-4 py-3 text-red-600 font-medium">
                                        <a href="{{ route('alumni.show', $a) }}" class="hover:underline">{{ $a->nim }}</a>
                                    </td>
                                    <td class="px-4 py-3">{{ $a->nama }}</td>
                                    <td class="px-4 py-3 text-blue-700">{{ $a->faculty?->name }}</td>
                                    <td class="px-4 py-3 text-blue-700">{{ $a->studyProgram?->name }}</td>
                                    <td class="px-4 py-3">{{ $a->studyProgram?->level ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $a->graduation_year }}</td>
                                    <td class="px-4 py-3">
                                        @if ($a->tracerResponse)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Sudah</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-600 text-white">Belum</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            @if ($canFillTracer)
                                                <a href="{{ route('tracer.edit', $a) }}" title="Isi Kuesioner Tracer"
                                                   class="inline-flex items-center justify-center h-8 w-8 rounded-md bg-green-600 text-white hover:bg-green-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            @endif
                                            @if ($canManageAlumni)
                                                <a href="{{ route('alumni.edit', $a) }}" title="Edit Data Alumni"
                                                   class="inline-flex items-center justify-center h-8 w-8 rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </a>
                                            @endif
                                            {{-- Always reachable: this is also where "Buat Tautan Form Pengguna Alumni" lives. --}}
                                            <a href="{{ route('alumni.show', $a) }}" title="Detail Alumni &amp; Form Pengguna Alumni"
                                               class="text-sm text-blue-600 hover:underline">Detail</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $alumni->links() }}
        </div>
    </div>
</x-app-layout>
