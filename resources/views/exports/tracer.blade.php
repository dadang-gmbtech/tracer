<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Ekspor Data Tracer') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <p class="text-sm text-gray-600">
                    Batasi rentang tahun lulus supaya file yang diunduh tidak terlalu besar dan tidak lama diproses.
                    Kosongkan salah satu atau kedua "Tahun" untuk tidak membatasi ke arah itu.
                </p>

                <form method="GET" action="{{ route('export.tracer') }}" class="space-y-4">
                    @if ($faculties->isNotEmpty())
                        <div>
                            <x-input-label for="faculty_id" value="Fakultas" />
                            <select id="faculty_id" name="faculty_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua Fakultas</option>
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

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="graduation_year_from" value="Tahun Lulus Dari" />
                            <select id="graduation_year_from" name="graduation_year_from" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">(tidak dibatasi)</option>
                                @foreach ($years as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="graduation_year_to" value="Tahun Lulus Sampai" />
                            <select id="graduation_year_to" name="graduation_year_to" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">(tidak dibatasi)</option>
                                @foreach ($years as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Unduh Excel</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
