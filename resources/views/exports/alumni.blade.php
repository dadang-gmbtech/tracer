<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Ekspor Data Alumni') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <p class="text-sm text-gray-600">
                    Batasi rentang tahun lulus supaya file yang diunduh tidak terlalu besar dan tidak lama diproses.
                    Kosongkan salah satu atau kedua "Tahun" untuk tidak membatasi ke arah itu.
                </p>

                <x-export-filter-form :action="route('export.alumni')" :years="$years" :faculties="$faculties" :program-studies="$programStudies" />
            </div>
        </div>
    </div>
</x-app-layout>
