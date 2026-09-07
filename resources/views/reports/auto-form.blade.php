<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Laporan Tracer Otomatis') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <p class="text-sm text-gray-600">
                    Menghasilkan dokumen PDF berisi ringkasan eksekutif, rekap per fakultas, tren beberapa tahun
                    terakhir, dan hasil kuesioner Pengguna Alumni — dibuat langsung dari data yang sudah ada, tanpa
                    perlu diunggah manual.
                </p>

                <form method="GET" action="{{ route('reports.auto.download') }}" class="space-y-4">
                    @if ($faculties->isNotEmpty())
                        <div>
                            <x-input-label for="faculty_id" value="Fakultas" />
                            <select id="faculty_id" name="faculty_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Seluruh Universitas</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="program_study_id" value="Program Studi" />
                            <select id="program_study_id" name="program_study_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua Program Studi</option>
                                @foreach ($programStudies as $ps)
                                    <option value="{{ $ps->id }}">{{ $ps->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <x-input-label for="year" value="Tahun Lulus" />
                        <x-text-input id="year" name="year" type="number" class="mt-1 w-full" value="{{ $defaultYear }}" required />
                        <p class="mt-1 text-xs text-gray-500">Tabel tren tetap menampilkan beberapa tahun terakhir untuk perbandingan.</p>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Unduh PDF</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
