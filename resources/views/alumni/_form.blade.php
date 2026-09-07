{{-- @props(['alumni' => null, 'faculties' => collect(), 'programStudies' => collect()]) --}}
@php
    $alumni ??= null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="nim" value="NIM" />
        <x-text-input id="nim" name="nim" class="mt-1 w-full" :value="old('nim', $alumni?->nim)" required />
        <x-input-error :messages="$errors->get('nim')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="nama" value="Nama" />
        <x-text-input id="nama" name="nama" class="mt-1 w-full" :value="old('nama', $alumni?->nama)" required />
        <x-input-error :messages="$errors->get('nama')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" type="email" name="email" class="mt-1 w-full" :value="old('email', $alumni?->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="phone" value="No Telp" />
        <x-text-input id="phone" name="phone" class="mt-1 w-full" :value="old('phone', $alumni?->phone)" />
        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="nik" value="NIK" />
        <x-text-input id="nik" name="nik" class="mt-1 w-full" :value="old('nik', $alumni?->nik)" />
        <x-input-error :messages="$errors->get('nik')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="npwp" value="NPWP" />
        <x-text-input id="npwp" name="npwp" class="mt-1 w-full" :value="old('npwp', $alumni?->npwp)" />
        <x-input-error :messages="$errors->get('npwp')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="graduation_year" value="Tahun Lulus" />
        <x-text-input id="graduation_year" name="graduation_year" class="mt-1 w-full" :value="old('graduation_year', $alumni?->graduation_year)" required />
        <x-input-error :messages="$errors->get('graduation_year')" class="mt-1" />
    </div>

    @if ($faculties->count() > 1)
        <div>
            <x-input-label for="faculty_id" value="Fakultas" />
            <select id="faculty_id" name="faculty_id" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                <option value="">Pilih Fakultas</option>
                @foreach ($faculties as $faculty)
                    <option value="{{ $faculty->id }}" @selected(old('faculty_id', $alumni?->faculty_id) == $faculty->id)>{{ $faculty->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('faculty_id')" class="mt-1" />
        </div>
    @elseif ($faculties->isNotEmpty())
        <div>
            <x-input-label value="Fakultas" />
            <p class="mt-1 text-sm text-gray-700 py-2">{{ $faculties->first()->name }}</p>
            <input type="hidden" name="faculty_id" value="{{ $faculties->first()->id }}">
        </div>
    @endif

    @if ($programStudies->count() > 1)
        <div>
            <x-input-label for="program_study_id" value="Program Studi" />
            <select id="program_study_id" name="program_study_id" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                <option value="">Pilih Program Studi</option>
                @foreach ($programStudies as $ps)
                    <option value="{{ $ps->id }}" @selected(old('program_study_id', $alumni?->program_study_id) == $ps->id)>{{ $ps->name }} ({{ $ps->level }})</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('program_study_id')" class="mt-1" />
        </div>
    @elseif ($programStudies->isNotEmpty())
        <div>
            <x-input-label value="Program Studi" />
            <p class="mt-1 text-sm text-gray-700 py-2">{{ $programStudies->first()->name }} ({{ $programStudies->first()->level }})</p>
            <input type="hidden" name="program_study_id" value="{{ $programStudies->first()->id }}">
        </div>
    @endif
</div>
