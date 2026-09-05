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
                    <x-input-label for="q" value="Cari (NIM/Nama)" />
                    <x-text-input id="q" name="q" class="mt-1" :value="request('q')" />
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
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Semua</option>
                        <option value="1" @selected(request('status') == 1)>Bekerja</option>
                        <option value="2" @selected(request('status') == 2)>Belum memungkinkan bekerja</option>
                        <option value="3" @selected(request('status') == 3)>Wiraswasta</option>
                        <option value="4" @selected(request('status') == 4)>Melanjutkan Pendidikan</option>
                        <option value="5" @selected(request('status') == 5)>Mencari kerja</option>
                    </select>
                </div>
                <x-primary-button>Cari</x-primary-button>
            </form>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3">NIM</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Prodi</th>
                            <th class="px-4 py-3">Tahun Lulus</th>
                            <th class="px-4 py-3">Status Tracer</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($alumni as $a)
                            <tr>
                                <td class="px-4 py-3">{{ $a->nim }}</td>
                                <td class="px-4 py-3">{{ $a->nama }}</td>
                                <td class="px-4 py-3">{{ $a->studyProgram?->name }}</td>
                                <td class="px-4 py-3">{{ $a->graduation_year }}</td>
                                <td class="px-4 py-3">
                                    @if ($a->tracerResponse)
                                        <span class="text-green-700">Sudah mengisi</span>
                                    @else
                                        <span class="text-gray-400">Belum mengisi</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('alumni.show', $a) }}" class="text-blue-600 hover:underline">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $alumni->links() }}
        </div>
    </div>
</x-app-layout>
