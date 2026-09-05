<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Program Studi') }}</h2>
            <a href="{{ route('admin.program-studies.create') }}" class="text-sm text-blue-600 hover:underline">+ Tambah Program Studi</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Jenjang</th>
                            <th class="px-4 py-3">Fakultas</th>
                            <th class="px-4 py-3">Alumni</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($programStudies as $ps)
                            <tr>
                                <td class="px-4 py-3">{{ $ps->code }}</td>
                                <td class="px-4 py-3">{{ $ps->name }}</td>
                                <td class="px-4 py-3">{{ $ps->level }}</td>
                                <td class="px-4 py-3">{{ $ps->faculty?->name }}</td>
                                <td class="px-4 py-3">{{ $ps->alumni_count }}</td>
                                <td class="px-4 py-3 space-x-3">
                                    <a href="{{ route('admin.program-studies.edit', $ps) }}" class="text-blue-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.program-studies.destroy', $ps) }}" class="inline" onsubmit="return confirm('Hapus program studi ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $programStudies->links() }}
        </div>
    </div>
</x-app-layout>
