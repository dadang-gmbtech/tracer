<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Tracer Study {{ $year }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 13px; margin-top: 22px; margin-bottom: 6px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        .header { text-align: center; margin-bottom: 18px; }
        .header .subtitle { font-size: 11px; color: #555; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; }
        .stats-grid td { border: none; padding: 4px; }
        .stat-box { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .stat-value { font-size: 15px; font-weight: bold; color: #1d4ed8; }
        .stat-label { font-size: 9px; color: #666; margin-top: 2px; }
        .muted { color: #888; }
        .footer { margin-top: 26px; font-size: 9px; color: #888; text-align: right; }
        .narrative { margin: 6px 0; text-align: justify; }
        .chart-row td { border: none; vertical-align: top; padding: 4px; }
        .legend { margin-top: 6px; }
        .legend-item { display: inline-block; margin-right: 10px; font-size: 10px; white-space: nowrap; }
        .legend-swatch { display: inline-block; width: 8px; height: 8px; margin-right: 3px; }
        .bar-chart .bar-row { margin-bottom: 5px; }
        .bar-chart .bar-label { font-size: 10px; margin-bottom: 1px; }
        .bar-chart .bar-track { background: #f0f1f3; height: 12px; }
        .bar-chart .bar-fill { height: 12px; background: #1d4ed8; }
        .bar-chart .bar-value { font-size: 9px; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN TRACER STUDY</h1>
        <div class="subtitle">Universitas Jenderal Soedirman{{ $scopeLabel ? ' — '.$scopeLabel : '' }}</div>
        <div class="subtitle">Tahun Lulus {{ $year }}</div>
    </div>

    @if (! $current)
        <p class="muted">Belum ada data alumni untuk tahun lulus {{ $year }} pada cakupan ini.</p>
    @else
        @php
            $tidakMengisi = max(0, $current['jumlah_alumni'] - $current['responden']);
            $responPercent = $current['persentase_responden'];
        @endphp

        {{-- 1. Responden Tracer Study --}}
        <h2>1. Responden Tracer Study</h2>
        <table class="chart-row">
            <tr>
                <td width="55%">
                    <table>
                        <thead><tr><th>Responden Tracer Study</th><th>Jumlah</th></tr></thead>
                        <tbody>
                            <tr><td>Responden</td><td>{{ number_format($current['responden'], 0, ',', '.') }}</td></tr>
                            <tr><td>Tidak Mengisi</td><td>{{ number_format($tidakMengisi, 0, ',', '.') }}</td></tr>
                            <tr><th>Total</th><th>{{ number_format($current['jumlah_alumni'], 0, ',', '.') }}</th></tr>
                        </tbody>
                    </table>
                </td>
                <td width="45%">
                    @php
                        $respondenSlices = \App\Support\PieChartSvg::slices(
                            ['Responden' => $current['responden'], 'Tidak Mengisi' => $tidakMengisi],
                            ['Responden' => '#1d4ed8', 'Tidak Mengisi' => '#f59e0b'],
                        );
                    @endphp
                    @if (count($respondenSlices))
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            @foreach ($respondenSlices as $slice)
                                <path d="{{ $slice['path'] }}" fill="{{ $slice['color'] }}" />
                            @endforeach
                        </svg>
                        <div class="legend">
                            @foreach ($respondenSlices as $slice)
                                <span class="legend-item"><span class="legend-swatch" style="background: {{ $slice['color'] }};"></span>{{ $slice['label'] }} {{ $slice['percent'] }}%</span>
                            @endforeach
                        </div>
                    @endif
                </td>
            </tr>
        </table>
        <p class="narrative">
            Pada tahun lulus {{ $year }}, cakupan ini memiliki populasi lulusan sebesar
            {{ number_format($current['jumlah_alumni'], 0, ',', '.') }} orang. Dari jumlah tersebut,
            {{ number_format($current['responden'], 0, ',', '.') }} orang ({{ $responPercent }}%) telah mengisi
            kuesioner Tracer Study, sedangkan {{ number_format($tidakMengisi, 0, ',', '.') }} orang
            ({{ round(100 - $responPercent, 1) }}%) belum mengisi.
        </p>

        {{-- 2. Sebaran Responden per Fakultas --}}
        @if ($facultyRecap['rows']->count() > 1)
            @php
                $facultySorted = $facultyRecap['rows']->sortByDesc('persentase_responden')->values();
                $topFaculty = $facultySorted->first();
                $bottomFaculty = $facultySorted->last();
            @endphp
            <h2>2. Sebaran Responden Tracer Study per Fakultas</h2>
            <table>
                <thead>
                    <tr><th>No</th><th>Fakultas</th><th>Lulusan</th><th>Responden</th><th>% Partisipasi</th></tr>
                </thead>
                <tbody>
                    @foreach ($facultySorted as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row['faculty'] }}</td>
                            <td>{{ $row['jumlah_alumni'] }}</td>
                            <td>{{ $row['responden'] }}</td>
                            <td>{{ $row['persentase_responden'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="bar-chart" style="margin-top: 8px;">
                @foreach ($facultySorted as $row)
                    <div class="bar-row">
                        <div class="bar-label">{{ $row['faculty'] }} ({{ $row['persentase_responden'] }}%)</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($row['persentase_responden'])) }}%;"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="narrative">
                Tingkat partisipasi tertinggi dicapai oleh {{ $topFaculty['faculty'] }} dengan
                {{ $topFaculty['persentase_responden'] }}% ({{ $topFaculty['responden'] }} dari
                {{ $topFaculty['jumlah_alumni'] }} lulusan), sedangkan tingkat partisipasi terendah ada pada
                {{ $bottomFaculty['faculty'] }} dengan {{ $bottomFaculty['persentase_responden'] }}%.
            </p>
        @endif

        {{-- 3. Status Lulusan --}}
        <h2>3. Status Lulusan Tahun {{ $year }}</h2>
        @php
            $status = $breakdown['status_lulusan'];
            $statusTotal = array_sum($status);
            $statusColors = ['bekerja' => '#1d4ed8', 'wiraswasta' => '#f59e0b', 'studi_lanjut' => '#9ca3af', 'belum_bekerja' => '#facc15'];
            $statusLabels = ['bekerja' => 'Bekerja', 'wiraswasta' => 'Wiraswasta', 'studi_lanjut' => 'Studi Lanjut', 'belum_bekerja' => 'Belum Bekerja'];
            $statusSlices = \App\Support\PieChartSvg::slices(
                collect($status)->mapWithKeys(fn ($v, $k) => [$statusLabels[$k] => $v])->all(),
                collect($statusColors)->mapWithKeys(fn ($v, $k) => [$statusLabels[$k] => $v])->all(),
            );
        @endphp
        @if ($statusTotal > 0)
            <table class="chart-row">
                <tr>
                    <td width="55%">
                        <table>
                            <thead><tr><th>Status</th><th>Jumlah</th><th>%</th></tr></thead>
                            <tbody>
                                @foreach ($statusLabels as $key => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td>{{ $status[$key] }}</td>
                                        <td>{{ round($status[$key] / $statusTotal * 100, 1) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                    <td width="45%">
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            @foreach ($statusSlices as $slice)
                                <path d="{{ $slice['path'] }}" fill="{{ $slice['color'] }}" />
                            @endforeach
                        </svg>
                        <div class="legend">
                            @foreach ($statusSlices as $slice)
                                <span class="legend-item"><span class="legend-swatch" style="background: {{ $slice['color'] }};"></span>{{ $slice['label'] }} {{ $slice['percent'] }}%</span>
                            @endforeach
                        </div>
                    </td>
                </tr>
            </table>
            <p class="narrative">
                Dari {{ $statusTotal }} responden pada tahun lulus {{ $year }}, mayoritas berstatus
                "{{ $statusLabels[array_search(max($status), $status)] }}" sebanyak {{ max($status) }} orang
                ({{ round(max($status) / $statusTotal * 100, 1) }}%).
            </p>
        @else
            <p class="muted">Belum ada responden dengan status terisi untuk tahun lulus ini.</p>
        @endif

        {{-- 4. Masa Tunggu --}}
        <h2>4. Masa Tunggu Mendapatkan Pekerjaan/Usaha Pertama</h2>
        @php
            $masaTunggu = $breakdown['masa_tunggu'];
            $masaTungguTotal = array_sum($masaTunggu);
            $masaTungguMax = max(1, ...array_values($masaTunggu));
        @endphp
        @if ($masaTungguTotal > 0)
            <div class="bar-chart">
                @foreach ($masaTunggu as $label => $value)
                    <div class="bar-row">
                        <div class="bar-label">{{ $label }} ({{ $value }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($value / $masaTungguMax * 100)) }}%;"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="narrative">
                Berdasarkan {{ $masaTungguTotal }} responden yang bekerja/berwirausaha, sebanyak
                {{ $masaTunggu['<=6 bulan'] }} orang ({{ round($masaTunggu['<=6 bulan'] / $masaTungguTotal * 100, 1) }}%)
                mendapatkan pekerjaan/usaha pertama dalam waktu kurang dari 6 bulan sejak kelulusan. Catatan: masa
                tunggu hanya dikumpulkan dari responden berstatus bekerja atau wiraswasta.
            </p>
        @else
            <p class="muted">Belum ada data masa tunggu untuk tahun lulus ini.</p>
        @endif

        {{-- 5. Besaran Pendapatan --}}
        <h2>5. Besaran Pendapatan per Bulan</h2>
        @php
            $pendapatan = $breakdown['pendapatan'];
            $pendapatanTotal = array_sum($pendapatan);
            $pendapatanMax = max(1, ...array_values($pendapatan));
        @endphp
        @if ($pendapatanTotal > 0)
            <div class="bar-chart">
                @foreach ($pendapatan as $label => $value)
                    <div class="bar-row">
                        <div class="bar-label">{{ $label }} ({{ $value }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($value / $pendapatanMax * 100)) }}%; background: #16a34a;"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="narrative">
                Rata-rata pendapatan responden yang bekerja/berwirausaha adalah
                Rp {{ number_format($current['rata_rata_penghasilan'], 0, ',', '.') }} per bulan, dari
                {{ $pendapatanTotal }} responden yang mengisi besaran pendapatannya.
            </p>
        @else
            <p class="muted">Belum ada data pendapatan untuk tahun lulus ini.</p>
        @endif

        {{-- 6. Sebaran Tempat Kerja --}}
        <h2>6. Sebaran Tempat Kerja</h2>
        @php
            $tempatKerja = collect($breakdown['tempat_kerja']);
            $tempatKerjaTop = $tempatKerja->take(10);
            $tempatKerjaMax = $tempatKerjaTop->max('jumlah') ?: 1;
        @endphp
        @if ($tempatKerja->isNotEmpty())
            <table>
                <thead><tr><th>No</th><th>Provinsi</th><th>Jumlah Alumni</th></tr></thead>
                <tbody>
                    @foreach ($tempatKerjaTop as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['jumlah'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="bar-chart" style="margin-top: 8px;">
                @foreach ($tempatKerjaTop as $row)
                    <div class="bar-row">
                        <div class="bar-label">{{ $row['name'] }} ({{ $row['jumlah'] }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($row['jumlah'] / $tempatKerjaMax * 100)) }}%; background: #7c3aed;"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="narrative">
                Provinsi tempat kerja terbanyak bagi lulusan {{ $year }} adalah
                {{ $tempatKerja->first()['name'] }} dengan {{ $tempatKerja->first()['jumlah'] }} orang.
                @if ($tempatKerja->count() > 10)
                    Tabel di atas menampilkan 10 provinsi teratas dari total {{ $tempatKerja->count() }} provinsi/wilayah.
                @endif
            </p>
        @else
            <p class="muted">Belum ada data lokasi tempat kerja untuk tahun lulus ini.</p>
        @endif
    @endif

    <h2>Tren Beberapa Tahun Terakhir</h2>
    <table>
        <thead>
            <tr>
                <th>Tahun</th>
                <th>Jumlah Lulusan</th>
                <th>Responden</th>
                <th>% Respon</th>
                <th>IKU Berdasar Lulusan</th>
                <th>Rata-rata Penghasilan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trendYears as $y)
                @php($row = $summary['data'][$y])
                <tr>
                    <td>{{ $y }}</td>
                    <td>{{ $row['jumlah_alumni'] }}</td>
                    <td>{{ $row['responden'] }}</td>
                    <td>{{ $row['persentase_responden'] }}%</td>
                    <td>{{ $row['iku_berdasar_lulusan'] }}%</td>
                    <td>Rp {{ number_format($row['rata_rata_penghasilan'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Hasil Kuesioner Pengguna Alumni</h2>
    @if ($employerSummary['total']['jumlah_respon'] > 0)
        <p>
            Indeks Kepuasan Keseluruhan: <strong>{{ $employerSummary['total']['indeks_keseluruhan'] }}%</strong>
            (dari {{ $employerSummary['total']['jumlah_respon'] }} respon, seluruh tahun)
        </p>
        <table>
            <thead><tr><th>Kompetensi</th><th>Indeks (%)</th></tr></thead>
            <tbody>
                @foreach ($employerSummary['total']['per_pertanyaan'] as $q)
                    <tr><td>{{ $q['label'] }}</td><td>{{ $q['indeks'] }}%</td></tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">Belum ada data kuesioner Pengguna Alumni untuk cakupan ini.</p>
    @endif

    <div class="footer">
        Dibuat otomatis oleh Sistem Tracer Studi UNSOED pada {{ $generatedAt->translatedFormat('d F Y H:i') }} WIB
        oleh {{ $generatedBy }}.
    </div>
</body>
</html>
