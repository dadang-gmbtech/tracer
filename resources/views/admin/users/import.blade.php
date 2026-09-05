<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Impor Surveyor') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <a href="{{ route('admin.users.template') }}" class="text-sm text-blue-600 hover:underline">Unduh Template Excel</a>

                <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="file" value="File Excel" />
                        <input id="file" type="file" name="file" class="mt-1 block w-full text-sm" required>
                        <x-input-error :messages="$errors->get('file')" class="mt-1" />
                    </div>
                    <div class="flex justify-end">
                        <x-primary-button>Impor</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
