<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Ekspor Data Pengguna Alumni') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <p class="text-sm text-gray-600">
                    Mengunduh hasil kuesioner Pengguna Alumni (penilaian dari perusahaan/instansi tempat alumni
                    bekerja). Filter di bawah ini merujuk ke tahun lulus alumni yang dinilai, bukan tanggal
                    penilaiannya diisi.
                </p>

                <x-export-filter-form :action="route('export.employer')" :years="$years" :faculties="$faculties" :program-studies="$programStudies" />
            </div>
        </div>
    </div>
</x-app-layout>
