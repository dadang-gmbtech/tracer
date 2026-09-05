<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pertanyaan Tambahan') }}</h2>
            <a href="{{ route('admin.questions.create') }}" class="text-sm text-blue-600 hover:underline">+ Tambah Pertanyaan</a>
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
                            <th class="px-4 py-3">Target</th>
                            <th class="px-4 py-3">Pertanyaan</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Aktif</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($questions as $q)
                            <tr>
                                <td class="px-4 py-3 capitalize">{{ $q->target }}</td>
                                <td class="px-4 py-3">{{ $q->label }}</td>
                                <td class="px-4 py-3">{{ $q->type }}</td>
                                <td class="px-4 py-3">{{ $q->is_active ? 'Ya' : 'Tidak' }}</td>
                                <td class="px-4 py-3 space-x-3">
                                    <a href="{{ route('admin.questions.edit', $q) }}" class="text-blue-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.questions.destroy', $q) }}" class="inline" onsubmit="return confirm('Hapus pertanyaan ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $questions->links() }}
        </div>
    </div>
</x-app-layout>
