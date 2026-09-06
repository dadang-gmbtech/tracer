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

    $palette = ['#1d4ed8', '#ea580c', '#16a34a', '#0891b2', '#a21caf', '#ca8a04', '#dc2626'];
    $questionFields = array_keys($total['per_pertanyaan']);
    $perPertanyaanConfig = $lineConfig(collect($questionFields)->map(fn ($field, $i) => [
        'label' => $total['per_pertanyaan'][$field]['label'],
        'data' => array_map(fn ($y) => $data[$y]['per_pertanyaan'][$field]['indeks'], $years),
        'borderColor' => $palette[$i % count($palette)],
        'tension' => 0.3,
    ])->values()->all());

    $facultyComparisonConfigs = [];
    if ($facultyComparison) {
        $fcYears = $facultyComparison['years'];
        $fcLabels = array_map(fn ($y) => (string) $y, $fcYears);

        foreach ($facultyComparison['per_pertanyaan'] as $field => $question) {
            $facultyNames = array_keys($question['series']);
            $facultyComparisonConfigs[$field] = [
                'label' => $question['label'],
                'config' => [
                    'type' => 'line',
                    'data' => [
                        'labels' => $fcLabels,
                        'datasets' => collect($question['series'])->map(fn ($byYear, $name) => [
                            'label' => $name,
                            'data' => array_map(fn ($y) => $byYear[$y], $fcYears),
                            'borderColor' => $palette[array_search($name, $facultyNames) % count($palette)],
                            'tension' => 0.3,
                        ])->values()->all(),
                    ],
                    'options' => ['responsive' => true, 'maintainAspectRatio' => false],
                ],
            ];
        }
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard Pengguna Alumni') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($faculties->isNotEmpty())
                <form method="GET" class="bg-white shadow-sm rounded-lg p-4 space-y-4">
                    <div class="flex flex-wrap gap-4 items-end">
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
                    </div>
                    <div>
                        <x-input-label value="Bandingkan Fakultas (grafik Indeks per Pertanyaan/Fakultas per Tahun)" />
                        <div class="mt-1 flex flex-wrap gap-3">
                            @foreach ($faculties as $faculty)
                                <label class="flex items-center gap-1 text-sm">
                                    <input type="checkbox" name="compare_faculty_ids[]" value="{{ $faculty->id }}"
                                           @checked(in_array($faculty->id, $selectedFacultyIds)) class="rounded">
                                    {{ $faculty->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
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

                @if ($facultyComparisonConfigs)
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @foreach ($facultyComparisonConfigs as $c)
                            <x-chart-card :title="'Perbandingan Fakultas — '.$c['label']" :config="$c['config']" />
                        @endforeach
                    </div>
                @else
                    <x-chart-card title="Indeks per Pertanyaan per Tahun" :config="$perPertanyaanConfig" height="360px" />
                @endif

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
                        <h3 class="text-base font-semibold text-gray-800">Rekap Pengguna Alumni Berdasarkan Fakultas</h3>
                        <form method="GET" class="flex gap-2 items-center text-sm">
                            @if (request('faculty_id')) <input type="hidden" name="faculty_id" value="{{ request('faculty_id') }}"> @endif
                            @if (request('program_study_id')) <input type="hidden" name="program_study_id" value="{{ request('program_study_id') }}"> @endif
                            @foreach ($selectedFacultyIds as $fid)
                                <input type="hidden" name="compare_faculty_ids[]" value="{{ $fid }}">
                            @endforeach
                            <label for="recap_year">Tahun</label>
                            <select id="recap_year" name="recap_year" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" @selected($facultyRecap['year'] == $y)>{{ $y }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    @if ($facultyRecap['rows']->count() > 1)
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
                    @else
                        <p class="text-sm text-gray-500">Belum ada data pengguna alumni pada tahun {{ $facultyRecap['year'] }}.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
