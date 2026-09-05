<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tambah Kabupaten/Kota') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.cities.store') }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="province_id" value="Provinsi" />
                    <select id="province_id" name="province_id" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                        <option value="">Pilih Provinsi</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" @selected(old('province_id') == $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('province_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="code" value="Kode (BPS, opsional)" />
                    <x-text-input id="code" name="code" class="mt-1 w-full" :value="old('code')" />
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="name" value="Nama Kabupaten/Kota" />
                    <x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div class="flex justify-end">
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
