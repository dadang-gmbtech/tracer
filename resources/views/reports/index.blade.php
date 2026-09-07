<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Laporan Tracer Studi') }}</h2>
            <div class="space-x-3 text-sm">
                @can('export-data')
                    <a href="{{ route('reports.auto.form') }}" class="text-blue-600 hover:underline">Laporan Otomatis (PDF)</a>
                @endcan
                @can('create', \App\Models\Report::class)
                    <a href="{{ route('reports.create') }}" class="text-blue-600 hover:underline">+ Unggah Laporan</a>
                @endcan
            </div>
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
                            <th class="px-4 py-3">Judul</th>
                            <th class="px-4 py-3">Level</th>
                            <th class="px-4 py-3">Tahun</th>
                            <th class="px-4 py-3">Diunggah oleh</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($reports as $report)
                            <tr>
                                <td class="px-4 py-3">{{ $report->title }}</td>
                                <td class="px-4 py-3 capitalize">{{ $report->level }} @if($report->faculty) — {{ $report->faculty->name }} @endif</td>
                                <td class="px-4 py-3">{{ $report->year }}</td>
                                <td class="px-4 py-3">{{ $report->uploader?->name }}</td>
                                <td class="px-4 py-3 space-x-3">
                                    <a href="{{ route('reports.download', $report) }}" class="text-blue-600 hover:underline">Unduh</a>
                                    @can('delete', $report)
                                        <form method="POST" action="{{ route('reports.destroy', $report) }}" class="inline" onsubmit="return confirm('Hapus laporan ini?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline">Hapus</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Belum ada laporan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $reports->links() }}
        </div>
    </div>
</x-app-layout>
