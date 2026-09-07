<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tambah Alumni') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('alumni.store') }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                @include('alumni._form', ['alumni' => null])
                <div class="flex justify-end gap-3">
                    <a href="{{ route('alumni.index') }}" class="text-sm text-gray-600 hover:underline self-center">Batal</a>
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
