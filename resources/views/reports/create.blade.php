<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Unggah Laporan Tracer Studi') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="title" value="Judul Laporan" />
                    <x-text-input id="title" name="title" class="mt-1 w-full" :value="old('title')" required />
                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="year" value="Tahun" />
                    <x-text-input id="year" type="number" name="year" class="mt-1 w-full" :value="old('year', now()->year)" required />
                    <x-input-error :messages="$errors->get('year')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="file" value="File (PDF/DOC/XLSX, maks 10MB)" />
                    <input id="file" type="file" name="file" class="mt-1 block w-full text-sm" required>
                    <x-input-error :messages="$errors->get('file')" class="mt-1" />
                </div>

                <p class="text-xs text-gray-500">Laporan akan tercatat pada level: <strong>
                    @if($user->hasAnyRole(['Super Admin', 'Admin Universitas'])) Universitas
                    @elseif($user->hasRole('Admin Fakultas')) Fakultas ({{ $user->faculty?->name }})
                    @else Program Studi ({{ $user->studyProgram?->name }})
                    @endif
                </strong></p>

                <div class="flex justify-end">
                    <x-primary-button>Unggah</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
