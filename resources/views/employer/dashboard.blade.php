@php
    $years = $summary['years'];
    $data = $summary['data'];
    $total = $summary['total'];
    $labels = array_map(fn ($y) => (string) $y, $years);

    $lineConfig = fn (array $datasets) => [
        'type' => 'line',
        'data' => ['labels' => $labels, 'datasets' => $datasets],
        'options' => ['responsive' => true, 'maintainAspectRatio' => false],
    ];

    $trenConfig = $lineConfig([
        ['label' => 'Jumlah Respon', 'data' => array_map(fn ($y) => $data[$y]['jumlah_respon'], $years), 'borderColor' => '#1d4ed8', 'tension' => 0.3],
    ]);

    $indeksTrenConfig = $lineConfig([
        ['label' => 'Indeks Kepuasan Keseluruhan (%)', 'data' => array_map(fn ($y) => $data[$y]['indeks_keseluruhan'], $years), 'borderColor' => '#16a34a', 'tension' => 0.3],
    ]);

    $perPertanyaanConfig = [
        'type' => 'bar',
        'data' => [
            'labels' => array_values(array_map(fn ($q) => $q['label'], $total['per_pertanyaan'])),
            'datasets' => [[
                'label' => 'Indeks (%)',
                'data' => array_values(array_map(fn ($q) => $q['indeks'], $total['per_pertanyaan'])),
                'backgroundColor' => '#0891b2',
            ]],
        ],
        'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'indexAxis' => 'y', 'scales' => ['x' => ['min' => 0, 'max' => 100]]],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard Pengguna Alumni') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($faculties->isNotEmpty())
                <form method="GET" class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap gap-4 items-end">
                    <div>
                        <x-input-label for="faculty_id" value="Fakultas" />
                        <select id="faculty_id" name="faculty_id" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Semua Fakultas</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}" @selected(request('faculty_id') == $faculty->id)>{{ $faculty->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="program_study_id" value="Program Studi" />
                        <select id="program_study_id" name="program_study_id" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Semua Program Studi</option>
                            @foreach ($programStudies as $ps)
                                <option value="{{ $ps->id }}" @selected(request('program_study_id') == $ps->id)>{{ $ps->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button>Filter</x-primary-button>
                </form>
            @endif

            <p class="text-xs text-gray-500">
                Indeks dihitung dari skala penilaian 1 (Sangat Baik) sampai 4 (Kurang), dibalik menjadi 0-100% —
                semakin tinggi persentasenya, semakin baik penilaian pengguna alumni terhadap lulusan.
            </p>

            @if ($total['jumlah_respon'] === 0)
                <div class="bg-white shadow-sm rounded-lg p-6 text-gray-500 text-sm">
                    Belum ada data pengguna alumni pada cakupan Anda.
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-2 gap-3 text-sm">
                    <div class="bg-green-50 rounded-md px-4 py-3">
                        <div class="text-xs text-gray-500">Jumlah Respon</div>
                        <div class="text-xl font-semibold text-gray-800">{{ $total['jumlah_respon'] }}</div>
                    </div>
                    <div class="bg-green-50 rounded-md px-4 py-3">
                        <div class="text-xs text-gray-500">Indeks Kepuasan Keseluruhan</div>
                        <div class="text-xl font-semibold text-gray-800">{{ $total['indeks_keseluruhan'] }}%</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <x-chart-card title="Jumlah Respon per Tahun" :config="$trenConfig" />
                    <x-chart-card title="Indeks Kepuasan per Tahun" :config="$indeksTrenConfig" />
                </div>

                <x-chart-card title="Indeks per Pertanyaan" :config="$perPertanyaanConfig" height="360px" />

                @if ($facultyRecap['rows']->count() > 1)
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Rekap Pengguna Alumni Berdasarkan Fakultas</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left border">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 border">#</th>
                                        <th class="px-3 py-2 border">Fakultas</th>
                                        <th class="px-3 py-2 border">Jumlah Respon</th>
                                        <th class="px-3 py-2 border">Indeks Kepuasan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($facultyRecap['rows'] as $i => $row)
                                        <tr class="odd:bg-white even:bg-gray-50">
                                            <td class="px-3 py-2 border">{{ $i + 1 }}</td>
                                            <td class="px-3 py-2 border">{{ $row['faculty'] }}</td>
                                            <td class="px-3 py-2 border">{{ $row['jumlah_respon'] }}</td>
                                            <td class="px-3 py-2 border">{{ $row['indeks_keseluruhan'] }}%</td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-green-50 font-semibold">
                                        <td class="px-3 py-2 border" colspan="2">Total</td>
                                        <td class="px-3 py-2 border">{{ $facultyRecap['total']['jumlah_respon'] }}</td>
                                        <td class="px-3 py-2 border">{{ $facultyRecap['total']['indeks_keseluruhan'] }}%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
