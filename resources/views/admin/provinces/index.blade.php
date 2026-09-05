<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Provinsi') }}</h2>
            <a href="{{ route('admin.provinces.create') }}" class="text-sm text-blue-600 hover:underline">+ Tambah Provinsi</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Jumlah Kab/Kota</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($provinces as $province)
                            <tr>
                                <td class="px-4 py-3">{{ $province->code }}</td>
                                <td class="px-4 py-3">{{ $province->name }}</td>
                                <td class="px-4 py-3">{{ $province->cities_count }}</td>
                                <td class="px-4 py-3 space-x-3">
                                    <a href="{{ route('admin.provinces.edit', $province) }}" class="text-blue-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.provinces.destroy', $province) }}" class="inline" onsubmit="return confirm('Hapus provinsi ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $provinces->links() }}
        </div>
    </div>
</x-app-layout>
