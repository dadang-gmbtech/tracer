<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tambah Program Studi') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.program-studies.store') }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="faculty_id" value="Fakultas" />
                    <select id="faculty_id" name="faculty_id" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                        <option value="">Pilih Fakultas</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}" @selected(old('faculty_id') == $faculty->id)>{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('faculty_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="code" value="Kode Program Studi (Dikti)" />
                    <x-text-input id="code" name="code" class="mt-1 w-full" :value="old('code')" required />
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="name" value="Nama Program Studi" />
                    <x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="level" value="Jenjang" />
                    <x-text-input id="level" name="level" class="mt-1 w-full" placeholder="S1 / D3 / S2" :value="old('level')" required />
                    <x-input-error :messages="$errors->get('level')" class="mt-1" />
                </div>
                <div class="flex justify-end">
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
