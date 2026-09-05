<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tambah Pertanyaan') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.questions.store') }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="target" value="Target Form" />
                    <select id="target" name="target" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                        <option value="tracer" @selected(old('target') == 'tracer')>Form Tracer Studi</option>
                        <option value="employer" @selected(old('target') == 'employer')>Form Pengguna Alumni</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="label" value="Teks Pertanyaan" />
                    <x-text-input id="label" name="label" class="mt-1 w-full" :value="old('label')" required />
                    <x-input-error :messages="$errors->get('label')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="type" value="Tipe Jawaban" />
                    <select id="type" name="type" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                        <option value="text">Teks</option>
                        <option value="number">Angka</option>
                        <option value="radio">Pilihan Tunggal</option>
                        <option value="checkbox">Pilihan Ganda</option>
                        <option value="select">Dropdown</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="options" value="Pilihan (satu per baris, untuk tipe pilihan/dropdown)" />
                    <textarea id="options" name="options" rows="4" class="mt-1 w-full rounded-md border-gray-300 text-sm">{{ old('options') }}</textarea>
                </div>
                <div>
                    <x-input-label for="order" value="Urutan" />
                    <x-text-input id="order" type="number" name="order" class="mt-1 w-32" :value="old('order', 0)" />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded">
                    Aktif
                </label>
                <div class="flex justify-end">
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
