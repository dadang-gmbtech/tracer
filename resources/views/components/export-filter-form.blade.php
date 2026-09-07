@props(['action', 'years', 'faculties', 'programStudies'])

<form method="GET" action="{{ $action }}" class="space-y-4">
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

    <div>
        <x-input-label value="Jenjang" />
        <div class="mt-1 flex gap-4 items-center">
            @foreach (['D3', 'S1', 'S2', 'S3'] as $level)
                <label class="flex items-center gap-1 text-sm">
                    <input type="checkbox" name="jenjang[]" value="{{ $level }}" class="rounded">
                    {{ $level }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end">
        <x-primary-button>Unduh Excel</x-primary-button>
    </div>
</form>
