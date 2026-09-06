@php
    $years = $summary['years'];
    $data = $summary['data'];
    $labels = array_map(fn ($y) => (string) $y, $years);

    $lineConfig = fn (array $datasets, array $extraOptions = []) => [
        'type' => 'line',
        'data' => ['labels' => $labels, 'datasets' => $datasets],
        'options' => array_merge(['responsive' => true, 'maintainAspectRatio' => false], $extraOptions),
    ];

    $palette = ['#1d4ed8', '#ea580c', '#16a34a', '#0891b2', '#a21caf', '#ca8a04', '#dc2626'];

    $mainConfig = $lineConfig([
        ['label' => 'Responden', 'data' => array_map(fn ($y) => $data[$y]['responden'], $years), 'borderColor' => $palette[0], 'tension' => 0.3],
        ['label' => 'Bekerja', 'data' => array_map(fn ($y) => $data[$y]['bekerja'], $years), 'borderColor' => $palette[1], 'tension' => 0.3],
        ['label' => 'Wiraswasta', 'data' => array_map(fn ($y) => $data[$y]['wiraswasta'], $years), 'borderColor' => $palette[2], 'tension' => 0.3],
        ['label' => 'Melanjutkan Studi', 'data' => array_map(fn ($y) => $data[$y]['melanjutkan_studi'], $years), 'borderColor' => $palette[3], 'tension' => 0.3],
        ['label' => 'Jumlah Alumni', 'data' => array_map(fn ($y) => $data[$y]['jumlah_alumni'], $years), 'borderColor' => $palette[4], 'tension' => 0.3],
    ]);

    $ikuConfig = $lineConfig([
        ['label' => '% Responden', 'data' => array_map(fn ($y) => $data[$y]['persentase_responden'], $years), 'borderColor' => $palette[0], 'tension' => 0.3],
        ['label' => '% IKU Berdasar Responden', 'data' => array_map(fn ($y) => $data[$y]['iku_berdasar_responden'], $years), 'borderColor' => $palette[1], 'tension' => 0.3],
        ['label' => '% IKU Berdasar Lulusan', 'data' => array_map(fn ($y) => $data[$y]['iku_berdasar_lulusan'], $years), 'borderColor' => $palette[2], 'tension' => 0.3],
    ]);

    $penghasilanConfig = $lineConfig([
        ['label' => 'Rata-rata Penghasilan', 'data' => array_map(fn ($y) => $data[$y]['rata_rata_penghasilan'], $years), 'borderColor' => $palette[0], 'tension' => 0.3],
    ]);

    $waktuTungguConfig = $lineConfig([
        ['label' => 'Rata-rata Waktu Bekerja (Bulan)', 'data' => array_map(fn ($y) => $data[$y]['rata_rata_waktu_tunggu'], $years), 'borderColor' => $palette[1], 'tension' => 0.3],
    ]);

    $posisiLabels = ['founder' => 'Founder', 'co_founder' => 'Co-Founder', 'staff' => 'Staff', 'freelance' => 'Freelance / Kerja Lepas'];
    $posisiConfig = $lineConfig(collect($posisiLabels)->map(fn ($label, $key) => [
        'label' => $label,
        'data' => array_map(fn ($y) => $data[$y]['posisi_wiraswasta'][$key], $years),
        'borderColor' => $palette[array_search($key, array_keys($posisiLabels))],
        'tension' => 0.3,
    ])->values()->all());

    $tempatLabels = [
        'instansi_pemerintah' => 'Instansi pemerintah', 'organisasi_non_profit' => 'Organisasi non-profit/LSM',
        'perusahaan_swasta' => 'Perusahaan swasta', 'wiraswasta_sendiri' => 'Wiraswasta/perusahaan sendiri',
        'lainnya' => 'Lainnya', 'bumn_bumd' => 'BUMN/BUMD', 'multilateral' => 'Institusi/Organisasi Multilateral',
    ];
    $tempatConfig = $lineConfig(collect($tempatLabels)->map(fn ($label, $key) => [
        'label' => $label,
        'data' => array_map(fn ($y) => $data[$y]['tempat_bekerja'][$key], $years),
        'borderColor' => $palette[array_search($key, array_keys($tempatLabels)) % count($palette)],
        'tension' => 0.3,
    ])->values()->all());
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard Tracer Studi') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form method="GET" class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap gap-4 items-end">
                @if ($faculties->isNotEmpty())
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
                @endif
                <div>
                    <x-input-label for="jenjang" value="Jenjang" />
                    <select id="jenjang" name="jenjang" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Semua Jenjang</option>
                        @foreach (['D3', 'S1', 'S2', 'S3'] as $level)
                            <option value="{{ $level }}" @selected(request('jenjang') === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Filter</x-primary-button>
            </form>

            <p class="text-xs text-gray-500 -mt-2">
                Catatan: IKU (grafik &amp; rekap fakultas) hanya dihitung untuk lulusan jenjang D3 dan S1, sesuai
                definisi resmi — lulusan S2/S3 tetap muncul di grafik lain (Responden, Bekerja, dst.) tapi tidak
                disertakan dalam persentase IKU.
            </p>

            @if (empty($years) || collect($data)->sum('jumlah_alumni') === 0)
                <div class="bg-white shadow-sm rounded-lg p-6 text-gray-500 text-sm">
                    Belum ada data alumni pada cakupan Anda.
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <x-chart-card title="Tracer Studi" :config="$mainConfig" />
                    <x-chart-card title="IKU" :config="$ikuConfig" />
                    <x-chart-card title="Rata-rata Penghasilan Alumni (Rp)" :config="$penghasilanConfig" />
                    <x-chart-card title="Rata-rata Waktu Bekerja (Bulan)" :config="$waktuTungguConfig" />
                    <x-chart-card title="Posisi Wiraswasta" :config="$posisiConfig" />
                    <x-chart-card title="Tempat Bekerja" :config="$tempatConfig" />
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
                        <h3 class="text-base font-semibold text-gray-800">Rekap Tracer Berdasarkan Fakultas</h3>
                        <form method="GET" class="flex gap-2 items-center text-sm">
                            @if(request('faculty_id')) <input type="hidden" name="faculty_id" value="{{ request('faculty_id') }}"> @endif
                            @if(request('program_study_id')) <input type="hidden" name="program_study_id" value="{{ request('program_study_id') }}"> @endif
                            @if(request('jenjang')) <input type="hidden" name="jenjang" value="{{ request('jenjang') }}"> @endif
                            <input type="hidden" name="year_a" value="{{ $yearA }}">
                            <input type="hidden" name="year_b" value="{{ $yearB }}">
                            <label for="recap_year">Tahun</label>
                            <select id="recap_year" name="recap_year" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" @selected($facultyRecap['year'] == $y)>{{ $y }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    @if ($facultyRecap['rows']->count() > 1)
                        <div class="overflow-x-auto mb-4">
                            <table class="min-w-full text-sm text-left border">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 border">#</th>
                                        <th class="px-3 py-2 border">Fakultas</th>
                                        <th class="px-3 py-2 border">Respon {{ $facultyRecap['year'] }}</th>
                                        <th class="px-3 py-2 border">Jumlah Alumni {{ $facultyRecap['year'] }}</th>
                                        <th class="px-3 py-2 border">Presentase Respon</th>
                                        <th class="px-3 py-2 border">IKU Berdasar Lulusan {{ $facultyRecap['year'] }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($facultyRecap['rows'] as $i => $row)
                                        <tr class="odd:bg-white even:bg-gray-50">
                                            <td class="px-3 py-2 border">{{ $i + 1 }}</td>
                                            <td class="px-3 py-2 border">{{ $row['faculty'] }}</td>
                                            <td class="px-3 py-2 border">{{ $row['responden'] }}</td>
                                            <td class="px-3 py-2 border">{{ $row['jumlah_alumni'] }}</td>
                                            <td class="px-3 py-2 border">{{ $row['persentase_responden'] }}%</td>
                                            <td class="px-3 py-2 border">{{ $row['iku_berdasar_lulusan'] }}%</td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-green-50 font-semibold">
                                        <td class="px-3 py-2 border" colspan="2">Total</td>
                                        <td class="px-3 py-2 border">{{ $facultyRecap['total']['responden'] }}</td>
                                        <td class="px-3 py-2 border">{{ $facultyRecap['total']['jumlah_alumni'] }}</td>
                                        <td class="px-3 py-2 border"></td>
                                        <td class="px-3 py-2 border"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                        @foreach ([
                            'Jumlah Lulusan' => $facultyRecap['total']['jumlah_alumni'],
                            'Capaian Responden' => $facultyRecap['total']['persentase_responden'].'%',
                            'Responden IKU Berdasar Lulusan' => $facultyRecap['total']['bobot_total'],
                            'Presentase IKU Berdasar Responden' => $facultyRecap['total']['iku_berdasar_responden'].'%',
                            'Presentase IKU Berdasar Lulusan' => $facultyRecap['total']['iku_berdasar_lulusan'].'%',
                            'Rata-rata Waktu Bekerja (Bulan)' => $facultyRecap['total']['rata_rata_waktu_tunggu'],
                            'Rata-rata Penghasilan' => 'Rp '.number_format($facultyRecap['total']['rata_rata_penghasilan'], 0, ',', '.'),
                        ] as $label => $value)
                            <div class="bg-green-50 rounded-md px-3 py-2">
                                <div class="text-xs text-gray-500">{{ $label }}</div>
                                <div class="font-semibold text-gray-800">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
                        <h3 class="text-base font-semibold text-gray-800">Rekap Bulanan</h3>
                        <form method="GET" class="flex gap-2 items-center text-sm">
                            @if(request('faculty_id')) <input type="hidden" name="faculty_id" value="{{ request('faculty_id') }}"> @endif
                            @if(request('program_study_id')) <input type="hidden" name="program_study_id" value="{{ request('program_study_id') }}"> @endif
                            @if(request('jenjang')) <input type="hidden" name="jenjang" value="{{ request('jenjang') }}"> @endif
                            <select name="year_a" class="rounded-md border-gray-300 text-sm">
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" @selected($yearA == $y)>{{ $y }}</option>
                                @endforeach
                            </select>
                            <span>vs</span>
                            <select name="year_b" class="rounded-md border-gray-300 text-sm">
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" @selected($yearB == $y)>{{ $y }}</option>
                                @endforeach
                            </select>
                            <button class="text-blue-600 underline">Terapkan</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 border">Bulan</th>
                                    <th class="px-3 py-2 border" colspan="4">{{ $yearA }}</th>
                                    <th class="px-3 py-2 border" colspan="4">{{ $yearB }}</th>
                                </tr>
                                <tr class="text-xs text-gray-500">
                                    <th class="px-3 py-2 border"></th>
                                    <th class="px-3 py-2 border">Bekerja</th>
                                    <th class="px-3 py-2 border">Rata Tunggu</th>
                                    <th class="px-3 py-2 border">Lanjut Studi</th>
                                    <th class="px-3 py-2 border">Wirausaha</th>
                                    <th class="px-3 py-2 border">Bekerja</th>
                                    <th class="px-3 py-2 border">Rata Tunggu</th>
                                    <th class="px-3 py-2 border">Lanjut Studi</th>
                                    <th class="px-3 py-2 border">Wirausaha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monthlyBreakdown as $row)
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="px-3 py-2 border font-medium">{{ \Carbon\Carbon::create()->month($row['month'])->translatedFormat('F') }}</td>
                                        <td class="px-3 py-2 border">{{ $row[$yearA]['jumlah_lulusan_bekerja'] }}</td>
                                        <td class="px-3 py-2 border">{{ $row[$yearA]['rata_rata_waktu_tunggu'] }}</td>
                                        <td class="px-3 py-2 border">{{ $row[$yearA]['jumlah_lanjut_studi'] }}</td>
                                        <td class="px-3 py-2 border">{{ $row[$yearA]['jumlah_wirausaha'] }}</td>
                                        <td class="px-3 py-2 border">{{ $row[$yearB]['jumlah_lulusan_bekerja'] }}</td>
                                        <td class="px-3 py-2 border">{{ $row[$yearB]['rata_rata_waktu_tunggu'] }}</td>
                                        <td class="px-3 py-2 border">{{ $row[$yearB]['jumlah_lanjut_studi'] }}</td>
                                        <td class="px-3 py-2 border">{{ $row[$yearB]['jumlah_wirausaha'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
