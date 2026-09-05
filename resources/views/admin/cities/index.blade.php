<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Kabupaten/Kota') }}</h2>
            <a href="{{ route('admin.cities.create') }}" class="text-sm text-blue-600 hover:underline">+ Tambah</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-sm font-medium text-gray-700 mb-2">Impor massal via Excel</p>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.cities.template') }}" class="text-sm text-blue-600 hover:underline">Unduh Template</a>
                    <form method="POST" action="{{ route('admin.cities.import') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf
                        <input type="file" name="file" required class="text-sm">
                        <x-secondary-button type="submit">Impor</x-secondary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Provinsi</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($cities as $city)
                            <tr>
                                <td class="px-4 py-3">{{ $city->code }}</td>
                                <td class="px-4 py-3">{{ $city->name }}</td>
                                <td class="px-4 py-3">{{ $city->province?->name }}</td>
                                <td class="px-4 py-3 space-x-3">
                                    <a href="{{ route('admin.cities.edit', $city) }}" class="text-blue-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.cities.destroy', $city) }}" class="inline" onsubmit="return confirm('Hapus data ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $cities->links() }}
        </div>
    </div>
</x-app-layout>
