<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Fakultas') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.faculties.update', $faculty) }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <x-input-label for="code" value="Kode Fakultas" />
                    <x-text-input id="code" name="code" class="mt-1 w-full" :value="old('code', $faculty->code)" required />
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="name" value="Nama Fakultas" />
                    <x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name', $faculty->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div class="flex justify-end">
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
