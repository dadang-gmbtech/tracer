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
        .analysis { margin: 6px 0 4px; padding: 6px 9px; background: #eff6ff; border-left: 3px solid #1d4ed8; text-align: justify; }
        .analysis-title { font-weight: bold; color: #1d4ed8; font-size: 10px; margin-bottom: 2px; }
        .summary { border: 1px solid #c7d2fe; background: #f8fafc; padding: 8px 12px; }
        .summary-title { font-weight: bold; font-size: 11px; margin-top: 6px; color: #1d4ed8; }
        .summary ul, .summary ol { margin: 3px 0 4px; padding-left: 18px; }
        .summary li { margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN TRACER STUDY</h1>
        <div class="subtitle">Universitas Jenderal Soedirman{{ $scopeLabel ? ' — '.$scopeLabel : '' }}</div>
        <div class="subtitle">Tahun Lulus {{ $year }}</div>
    </div>

    @php
        // Every figure is shown as a count and as a share: $n(1234) -> "1.234",
        // $p(30, 120) -> "25,0%", $pct(25) -> "25,0%".
        $n = fn ($value) => number_format((float) $value, 0, ',', '.');
        $pct = fn ($percent) => number_format((float) $percent, 1, ',', '.').'%';
        $p = fn ($part, $total) => $pct($total > 0 ? $part / $total * 100 : 0);
    @endphp

    @if (! $current)
        <p class="muted">Belum ada data alumni untuk tahun lulus {{ $year }} pada cakupan ini.</p>
    @else
        @php
            $tidakMengisi = max(0, $current['jumlah_alumni'] - $current['responden']);
            $responPercent = $current['persentase_responden'];
            $section = 0;
        @endphp

        @php $ringkasan = $analysis['ringkasan'] ?? [] @endphp
        @if (! empty($ringkasan['temuan']))
            <h2>Ringkasan Eksekutif</h2>
            <div class="summary">
                <div class="summary-title">Temuan utama</div>
                <ul>
                    @foreach ($ringkasan['temuan'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>

                @if (! empty($ringkasan['baik']))
                    <div class="summary-title" style="color: #15803d;">Capaian yang baik</div>
                    <ul>
                        @foreach ($ringkasan['baik'] as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (! empty($ringkasan['perhatian']))
                    <div class="summary-title" style="color: #b45309;">Perlu perhatian</div>
                    <ul>
                        @foreach ($ringkasan['perhatian'] as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (! empty($ringkasan['rekomendasi']))
                    <div class="summary-title">Rekomendasi prioritas</div>
                    <ol>
                        @foreach ($ringkasan['rekomendasi'] as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        {{-- Responden Tracer Study --}}
        <h2>{{ ++$section }}. Responden Tracer Study</h2>
        <table class="chart-row">
            <tr>
                <td width="55%">
                    <table>
                        <thead><tr><th>Responden Tracer Study</th><th>Jumlah</th><th>%</th></tr></thead>
                        <tbody>
                            <tr><td>Responden</td><td>{{ $n($current['responden']) }}</td><td>{{ $p($current['responden'], $current['jumlah_alumni']) }}</td></tr>
                            <tr><td>Tidak Mengisi</td><td>{{ $n($tidakMengisi) }}</td><td>{{ $p($tidakMengisi, $current['jumlah_alumni']) }}</td></tr>
                            <tr><th>Total</th><th>{{ $n($current['jumlah_alumni']) }}</th><th>{{ $p($current['jumlah_alumni'], $current['jumlah_alumni']) }}</th></tr>
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
                                <span class="legend-item"><span class="legend-swatch" style="background: {{ $slice['color'] }};"></span>{{ $slice['label'] }}: {{ $n($slice['value']) }} ({{ $pct($slice['percent']) }})</span>
                            @endforeach
                        </div>
                    @endif
                </td>
            </tr>
        </table>
        <p class="narrative">
            Pada tahun lulus {{ $year }}, cakupan ini memiliki populasi lulusan sebesar
            {{ $n($current['jumlah_alumni']) }} orang (100%). Dari jumlah tersebut,
            {{ $n($current['responden']) }} orang ({{ $p($current['responden'], $current['jumlah_alumni']) }}) telah mengisi
            kuesioner Tracer Study, sedangkan {{ $n($tidakMengisi) }} orang
            ({{ $p($tidakMengisi, $current['jumlah_alumni']) }}) belum mengisi.
        </p>
        @include('reports.partials.analysis', ['lines' => $analysis['sections']['responden'] ?? []])

        {{-- Status Lulusan --}}
        <h2>{{ ++$section }}. Status Lulusan Tahun {{ $year }}</h2>
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
                                        <td>{{ $n($status[$key]) }}</td>
                                        <td>{{ $p($status[$key], $statusTotal) }}</td>
                                    </tr>
                                @endforeach
                                <tr><th>Total</th><th>{{ $n($statusTotal) }}</th><th>{{ $p($statusTotal, $statusTotal) }}</th></tr>
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
                                <span class="legend-item"><span class="legend-swatch" style="background: {{ $slice['color'] }};"></span>{{ $slice['label'] }}: {{ $n($slice['value']) }} ({{ $pct($slice['percent']) }})</span>
                            @endforeach
                        </div>
                    </td>
                </tr>
            </table>
            <p class="narrative">
                Dari {{ $n($statusTotal) }} responden pada tahun lulus {{ $year }}, mayoritas berstatus
                "{{ $statusLabels[array_search(max($status), $status)] }}" sebanyak {{ $n(max($status)) }} orang
                ({{ $p(max($status), $statusTotal) }}).
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['status'] ?? []])
        @else
            <p class="muted">Belum ada responden dengan status terisi untuk tahun lulus ini.</p>
        @endif

        {{-- Sebaran Responden per Fakultas --}}
        @if ($facultyRecap['rows']->count() > 1)
            @php
                $facultySorted = $facultyRecap['rows']->sortByDesc('persentase_responden')->values();
                $topFaculty = $facultySorted->first();
                $bottomFaculty = $facultySorted->last();
            @endphp
            <h2>{{ ++$section }}. Sebaran Responden Tracer Study per Fakultas</h2>
            <table>
                <thead>
                    <tr><th>No</th><th>Fakultas</th><th>Lulusan</th><th>Responden</th><th>% Partisipasi</th></tr>
                </thead>
                <tbody>
                    @foreach ($facultySorted as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row['faculty'] }}</td>
                            <td>{{ $n($row['jumlah_alumni']) }}</td>
                            <td>{{ $n($row['responden']) }}</td>
                            <td>{{ $pct($row['persentase_responden']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="bar-chart" style="margin-top: 8px;">
                @foreach ($facultySorted as $row)
                    <div class="bar-row">
                        <div class="bar-label">{{ $row['faculty'] }}: {{ $n($row['responden']) }} dari {{ $n($row['jumlah_alumni']) }} lulusan ({{ $pct($row['persentase_responden']) }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($row['persentase_responden'])) }}%;"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="narrative">
                Tingkat partisipasi tertinggi dicapai oleh {{ $topFaculty['faculty'] }} dengan
                {{ $pct($topFaculty['persentase_responden']) }} ({{ $n($topFaculty['responden']) }} dari
                {{ $n($topFaculty['jumlah_alumni']) }} lulusan), sedangkan tingkat partisipasi terendah ada pada
                {{ $bottomFaculty['faculty'] }} dengan {{ $pct($bottomFaculty['persentase_responden']) }}
                ({{ $n($bottomFaculty['responden']) }} dari {{ $n($bottomFaculty['jumlah_alumni']) }} lulusan).
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['fakultas'] ?? []])
        @endif

        {{-- Masa Tunggu --}}
        <h2>{{ ++$section }}. Masa Tunggu Mendapatkan Pekerjaan/Usaha Pertama</h2>
        @php
            $masaTunggu = $breakdown['masa_tunggu'];
            $masaTungguTotal = array_sum($masaTunggu);
            $masaTungguMax = max(1, ...array_values($masaTunggu));
        @endphp
        @if ($masaTungguTotal > 0)
            <div class="bar-chart">
                @foreach ($masaTunggu as $label => $value)
                    <div class="bar-row">
                        <div class="bar-label">{{ $label }}: {{ $n($value) }} orang ({{ $p($value, $masaTungguTotal) }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($value / $masaTungguMax * 100)) }}%;"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="narrative">
                Berdasarkan {{ $n($masaTungguTotal) }} responden yang bekerja/berwirausaha, sebanyak
                {{ $n($masaTunggu['<=6 bulan']) }} orang ({{ $p($masaTunggu['<=6 bulan'], $masaTungguTotal) }})
                mendapatkan pekerjaan/usaha pertama dalam waktu kurang dari 6 bulan sejak kelulusan. Catatan: masa
                tunggu hanya dikumpulkan dari responden berstatus bekerja atau wiraswasta.
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['masa_tunggu'] ?? []])
        @else
            <p class="muted">Belum ada data masa tunggu untuk tahun lulus ini.</p>
        @endif

        {{-- Besaran Pendapatan --}}
        <h2>{{ ++$section }}. Besaran Pendapatan per Bulan</h2>
        @php
            $pendapatan = $breakdown['pendapatan'];
            $pendapatanTotal = array_sum($pendapatan);
            $pendapatanMax = max(1, ...array_values($pendapatan));
        @endphp
        @if ($pendapatanTotal > 0)
            <div class="bar-chart">
                @foreach ($pendapatan as $label => $value)
                    <div class="bar-row">
                        <div class="bar-label">{{ $label }}: {{ $n($value) }} orang ({{ $p($value, $pendapatanTotal) }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($value / $pendapatanMax * 100)) }}%; background: #16a34a;"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="narrative">
                Rata-rata pendapatan adalah Rp {{ number_format($breakdown['rata_rata_pendapatan'], 0, ',', '.') }}
                per bulan, dihitung dari {{ $n($pendapatanTotal) }} responden yang mengisi besaran pendapatannya
                (isian kosong tidak ikut dihitung).
                @php $pendapatanTop = array_search(max($pendapatan), $pendapatan) @endphp
                Kelompok terbanyak berpendapatan {{ $pendapatanTop }} per bulan, yaitu {{ $n($pendapatan[$pendapatanTop]) }} orang
                ({{ $p($pendapatan[$pendapatanTop], $pendapatanTotal) }}).
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['pendapatan'] ?? []])
        @else
            <p class="muted">Belum ada data pendapatan untuk tahun lulus ini.</p>
        @endif

        {{-- Rincian IKU --}}
        @php $iku = $breakdown['iku'] @endphp
        @if ($iku['lulusan'] > 0)
            <h2>{{ ++$section }}. Rincian IKU (Lulusan D3/S1)</h2>
            <table>
                <thead><tr><th>Komponen</th><th>Jumlah Lulusan</th><th>% Lulusan</th><th>Bobot IKU</th><th>% dari Total Bobot</th></tr></thead>
                <tbody>
                    @foreach (['bekerja' => 'Bekerja', 'wiraswasta' => 'Wiraswasta', 'studi_lanjut' => 'Studi Lanjut', 'lainnya' => 'Belum bekerja / mencari kerja'] as $key => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $n($iku['komponen'][$key]['jumlah']) }}</td>
                            <td>{{ $p($iku['komponen'][$key]['jumlah'], $iku['lulusan']) }}</td>
                            <td>{{ number_format($iku['komponen'][$key]['bobot'], 2, ',', '.') }}</td>
                            <td>{{ $p($iku['komponen'][$key]['bobot'], $iku['bobot_total']) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td>Belum mengisi kuesioner</td>
                        <td>{{ $n($iku['tidak_mengisi']) }}</td>
                        <td>{{ $p($iku['tidak_mengisi'], $iku['lulusan']) }}</td>
                        <td>0,00</td>
                        <td>0,0%</td>
                    </tr>
                    <tr>
                        <th>Total</th>
                        <th>{{ $n($iku['lulusan']) }}</th>
                        <th>{{ $p($iku['lulusan'], $iku['lulusan']) }}</th>
                        <th>{{ number_format($iku['bobot_total'], 2, ',', '.') }}</th>
                        <th>{{ $pct($iku['nilai']) }} IKU</th>
                    </tr>
                </tbody>
            </table>
            <p class="narrative">
                IKU berdasar lulusan = total bobot &divide; jumlah lulusan D3/S1 = {{ number_format($iku['bobot_total'], 2, ',', '.') }}
                &divide; {{ $n($iku['lulusan']) }} = {{ $pct($iku['nilai']) }}. Bobot tiap lulusan bergantung pada status, masa tunggu,
                dan gaji dibanding 1,2 &times; UMP provinsi tempat kerja.
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['iku'] ?? []])
        @endif

        {{-- Sebaran Tempat Kerja --}}
        <h2>{{ ++$section }}. Sebaran Tempat Kerja</h2>
        @php
            $tempatKerja = collect($breakdown['tempat_kerja']);
            $tempatKerjaTop = $tempatKerja->take(10);
            $tempatKerjaMax = $tempatKerjaTop->max('jumlah') ?: 1;
            $tempatKerjaTotal = $tempatKerja->sum('jumlah');
        @endphp
        @if ($tempatKerja->isNotEmpty())
            <table>
                <thead><tr><th>No</th><th>Provinsi</th><th>Jumlah Alumni</th><th>%</th></tr></thead>
                <tbody>
                    @foreach ($tempatKerjaTop as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $n($row['jumlah']) }}</td>
                            <td>{{ $p($row['jumlah'], $tempatKerjaTotal) }}</td>
                        </tr>
                    @endforeach
                    @if ($tempatKerja->count() > 10)
                        @php $lainnya = $tempatKerja->slice(10)->sum('jumlah') @endphp
                        <tr>
                            <td></td>
                            <td>{{ $tempatKerja->count() - 10 }} provinsi/wilayah lainnya</td>
                            <td>{{ $n($lainnya) }}</td>
                            <td>{{ $p($lainnya, $tempatKerjaTotal) }}</td>
                        </tr>
                    @endif
                    <tr><th></th><th>Total</th><th>{{ $n($tempatKerjaTotal) }}</th><th>{{ $p($tempatKerjaTotal, $tempatKerjaTotal) }}</th></tr>
                </tbody>
            </table>
            <div class="bar-chart" style="margin-top: 8px;">
                @foreach ($tempatKerjaTop as $row)
                    <div class="bar-row">
                        <div class="bar-label">{{ $row['name'] }}: {{ $n($row['jumlah']) }} orang ({{ $p($row['jumlah'], $tempatKerjaTotal) }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($row['jumlah'] / $tempatKerjaMax * 100)) }}%; background: #7c3aed;"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="narrative">
                Provinsi tempat kerja terbanyak bagi lulusan {{ $year }} adalah
                {{ $tempatKerja->first()['name'] }} dengan {{ $n($tempatKerja->first()['jumlah']) }} orang
                ({{ $p($tempatKerja->first()['jumlah'], $tempatKerjaTotal) }} dari {{ $n($tempatKerjaTotal) }} responden yang
                mengisi lokasi kerja).
                @if ($tempatKerja->count() > 10)
                    Tabel di atas merinci 10 provinsi teratas dari total {{ $tempatKerja->count() }} provinsi/wilayah.
                @endif
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['tempat_kerja'] ?? []])
        @else
            <p class="muted">Belum ada data lokasi tempat kerja untuk tahun lulus ini.</p>
        @endif

        {{-- Relevansi Bidang Studi (F14) --}}
        <h2>{{ ++$section }}. Relevansi Bidang Studi dengan Pekerjaan</h2>
        @php
            $f14 = $breakdown['f14'];
            $f14Total = array_sum($f14);
            $f14Max = max(1, ...array_values($f14));
        @endphp
        @if ($f14Total > 0)
            <div class="bar-chart">
                @foreach ($f14 as $label => $value)
                    <div class="bar-row">
                        <div class="bar-label">{{ $label }}: {{ $n($value) }} orang ({{ $p($value, $f14Total) }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($value / $f14Max * 100)) }}%;"></div></div>
                    </div>
                @endforeach
            </div>
            @php $f14Top = array_search(max($f14), $f14) @endphp
            <p class="narrative">
                Dari {{ $n($f14Total) }} responden yang mengisi, {{ $n($f14[$f14Top]) }} orang ({{ $p($f14[$f14Top], $f14Total) }})
                menilai pekerjaannya "{{ strtolower($f14Top) }}" dengan bidang studinya.
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['f14'] ?? []])
        @else
            <p class="muted">Belum ada data relevansi bidang studi untuk tahun lulus ini.</p>
        @endif

        {{-- Kesesuaian Jenjang Pendidikan (F15) --}}
        <h2>{{ ++$section }}. Kesesuaian Jenjang Pendidikan</h2>
        @php
            $f15 = $breakdown['f15'];
            $f15Total = array_sum($f15);
            $f15Max = max(1, ...array_values($f15));
        @endphp
        @if ($f15Total > 0)
            <div class="bar-chart">
                @foreach ($f15 as $label => $value)
                    <div class="bar-row">
                        <div class="bar-label">{{ $label }}: {{ $n($value) }} orang ({{ $p($value, $f15Total) }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($value / $f15Max * 100)) }}%;"></div></div>
                    </div>
                @endforeach
            </div>
            @php $f15Top = array_search(max($f15), $f15) @endphp
            <p class="narrative">
                Dari {{ $n($f15Total) }} responden yang mengisi, {{ $n($f15[$f15Top]) }} orang ({{ $p($f15[$f15Top], $f15Total) }})
                bekerja pada "{{ strtolower($f15Top) }}" dengan jenjang pendidikannya.
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['f15'] ?? []])
        @else
            <p class="muted">Belum ada data kesesuaian jenjang pendidikan untuk tahun lulus ini.</p>
        @endif

        {{-- Jenis Perusahaan/Instansi (F1101) --}}
        <h2>{{ ++$section }}. Jenis Perusahaan/Instansi Tempat Bekerja</h2>
        @php
            $jenisInstansi = $breakdown['jenis_instansi'];
            $jenisInstansiTotal = array_sum($jenisInstansi);
            $jenisInstansiMax = max(1, ...array_values($jenisInstansi));
        @endphp
        @if ($jenisInstansiTotal > 0)
            <div class="bar-chart">
                @foreach ($jenisInstansi as $label => $value)
                    <div class="bar-row">
                        <div class="bar-label">{{ $label }}: {{ $n($value) }} orang ({{ $p($value, $jenisInstansiTotal) }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($value / $jenisInstansiMax * 100)) }}%; background: #0891b2;"></div></div>
                    </div>
                @endforeach
            </div>
            @php $jenisInstansiTop = array_key_first($jenisInstansi) @endphp
            <p class="narrative">
                Dari {{ $n($jenisInstansiTotal) }} responden bekerja yang mengisi, {{ $n($jenisInstansi[$jenisInstansiTop]) }} orang
                ({{ $p($jenisInstansi[$jenisInstansiTop], $jenisInstansiTotal) }}) bekerja di {{ strtolower($jenisInstansiTop) }}.
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['jenis_instansi'] ?? []])
        @else
            <p class="muted">Belum ada data jenis perusahaan/instansi untuk tahun lulus ini.</p>
        @endif

        {{-- Ruang Lingkup Tempat Bekerja (F5D) --}}
        <h2>{{ ++$section }}. Ruang Lingkup Tempat Bekerja</h2>
        @php
            $ruangLingkup = $breakdown['ruang_lingkup'];
            $ruangLingkupTotal = array_sum($ruangLingkup);
            $ruangLingkupMax = max(1, ...array_values($ruangLingkup));
        @endphp
        @if ($ruangLingkupTotal > 0)
            <div class="bar-chart">
                @foreach ($ruangLingkup as $label => $value)
                    <div class="bar-row">
                        <div class="bar-label">{{ $label }}: {{ $n($value) }} orang ({{ $p($value, $ruangLingkupTotal) }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($value / $ruangLingkupMax * 100)) }}%; background: #0891b2;"></div></div>
                    </div>
                @endforeach
            </div>
            @php $ruangLingkupTop = array_search(max($ruangLingkup), $ruangLingkup) @endphp
            <p class="narrative">
                Dari {{ $n($ruangLingkupTotal) }} responden yang mengisi, {{ $n($ruangLingkup[$ruangLingkupTop]) }} orang
                ({{ $p($ruangLingkup[$ruangLingkupTop], $ruangLingkupTotal) }}) bekerja di lingkup {{ strtolower($ruangLingkupTop) }}.
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['ruang_lingkup'] ?? []])
        @else
            <p class="muted">Belum ada data ruang lingkup tempat bekerja untuk tahun lulus ini.</p>
        @endif

        {{-- Bagaimana Mencari Pekerjaan (F401-F416) --}}
        <h2>{{ ++$section }}. Bagaimana Mencari Pekerjaan</h2>
        @php
            $caraMencariKerja = $breakdown['cara_mencari_kerja'];
        @endphp
        @if ($caraMencariKerja['basis'] > 0)
            <div class="bar-chart">
                @foreach ($caraMencariKerja['items'] as $label => $item)
                    <div class="bar-row">
                        <div class="bar-label">{{ $label }}: {{ $n($item['jumlah']) }} orang ({{ $pct($item['persen']) }})</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round($item['persen'])) }}%; background: #be185d;"></div></div>
                    </div>
                @endforeach
            </div>
            @php $caraTop = array_key_first($caraMencariKerja['items']) @endphp
            <p class="narrative">
                Basis: {{ $n($caraMencariKerja['basis']) }} responden berstatus bekerja/mencari kerja (boleh memilih lebih
                dari satu cara, sehingga total persentase bisa melebihi 100%). "{{ $caraTop }}" jadi cara paling umum,
                dipilih oleh {{ $n($caraMencariKerja['items'][$caraTop]['jumlah']) }} orang
                ({{ $pct($caraMencariKerja['items'][$caraTop]['persen']) }}).
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['cara_mencari_kerja'] ?? []])
        @else
            <p class="muted">Belum ada data cara mencari pekerjaan untuk tahun lulus ini.</p>
        @endif

        {{-- Kesenjangan Kompetensi (F1761-F1782) --}}
        @php $kompetensi = collect($breakdown['kompetensi'])->sortByDesc('selisih')->values() @endphp
        @if ($kompetensi->isNotEmpty())
            <h2>{{ ++$section }}. Kesenjangan Kompetensi Lulusan</h2>
            <p class="muted" style="margin: 0 0 4px;">
                Skala 1 (Sangat Rendah) &ndash; 5 (Sangat Tinggi). Selisih = dibutuhkan pekerjaan &minus; dikuasai saat lulus;
                selisih positif berarti pekerjaan menuntut lebih dari yang dikuasai.
            </p>
            <table>
                <thead><tr><th>Kompetensi</th><th>Dikuasai Saat Lulus</th><th>Dibutuhkan Pekerjaan</th><th>Selisih</th><th>Responden</th></tr></thead>
                <tbody>
                    @foreach ($kompetensi as $k)
                        <tr>
                            <td>{{ $k['label'] }}</td>
                            <td>{{ number_format($k['saat_lulus'], 2, ',', '.') }}</td>
                            <td>{{ number_format($k['dibutuhkan'], 2, ',', '.') }}</td>
                            <td style="{{ $k['selisih'] >= \App\Services\ReportAnalysisService::KESENJANGAN_KOMPETENSI ? 'color: #b45309; font-weight: bold;' : '' }}">
                                {{ ($k['selisih'] > 0 ? '+' : '').number_format($k['selisih'], 2, ',', '.') }}
                            </td>
                            <td>{{ $n($k['n']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['kompetensi'] ?? []])
        @endif

        {{-- Penekanan Metode Pembelajaran (F21-F37) --}}
        @php
            $metode = collect($breakdown['metode_pembelajaran'])->sortBy('rata_rata')->values();
        @endphp
        @if ($metode->isNotEmpty())
            <h2>{{ ++$section }}. Penekanan Metode/Bentuk Pembelajaran di Program Studi</h2>
            <p class="muted" style="margin: 0 0 4px;">Skala 1 (Sangat Besar) &ndash; 5 (Tidak Ada); angka lebih kecil berarti penekanan lebih besar, ditunjukkan dengan batang yang lebih panjang.</p>
            <div class="bar-chart">
                @foreach ($metode as $m)
                    <div class="bar-row">
                        <div class="bar-label">{{ $m['label'] }}: rata-rata {{ number_format($m['rata_rata'], 2, ',', '.') }} dari {{ $n($m['n']) }} responden</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, round((5 - $m['rata_rata']) / 4 * 100)) }}%; background: #ca8a04;"></div></div>
                    </div>
                @endforeach
            </div>
            @php $metodeTop = $metode->first() @endphp
            <p class="narrative">
                "{{ $metodeTop['label'] }}" dinilai paling ditekankan di program studi (rata-rata
                {{ number_format($metodeTop['rata_rata'], 2, ',', '.') }} dari skala 1&ndash;5, {{ $n($metodeTop['n']) }} responden).
            </p>
            @include('reports.partials.analysis', ['lines' => $analysis['sections']['metode'] ?? []])
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
                @php $row = $summary['data'][$y] @endphp
                <tr>
                    <td>{{ $y }}</td>
                    <td>{{ $n($row['jumlah_alumni']) }}</td>
                    <td>{{ $n($row['responden']) }}</td>
                    <td>{{ $pct($row['persentase_responden']) }}</td>
                    <td>{{ $pct($row['iku_berdasar_lulusan']) }}</td>
                    <td>Rp {{ $n($row['rata_rata_penghasilan']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Hasil Kuesioner Pengguna Alumni</h2>
    @if ($employerSummary['total']['jumlah_respon'] > 0)
        <p>
            Indeks Kepuasan Keseluruhan: <strong>{{ $pct($employerSummary['total']['indeks_keseluruhan']) }}</strong>
            (dari {{ $n($employerSummary['total']['jumlah_respon']) }} respon, seluruh tahun)
        </p>
        @php $skala = [1 => 'Sangat Baik', 2 => 'Baik', 3 => 'Cukup', 4 => 'Kurang'] @endphp
        <table>
            <thead>
                <tr>
                    <th>Kompetensi</th>
                    @foreach ($skala as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                    <th>Indeks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($employerSummary['total']['per_pertanyaan'] as $q)
                    @php $terisi = array_sum($q['distribusi']) @endphp
                    <tr>
                        <td>{{ $q['label'] }}</td>
                        @foreach ($skala as $nilai => $label)
                            <td>{{ $n($q['distribusi'][$nilai] ?? 0) }} ({{ $p($q['distribusi'][$nilai] ?? 0, $terisi) }})</td>
                        @endforeach
                        <td>{{ $pct($q['indeks']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="muted" style="margin-top: 4px; font-size: 9px;">
            Setiap sel: jumlah penilai (persentase dari penilai kompetensi tersebut). Indeks 100% berarti semua menilai Sangat Baik.
        </p>
        @include('reports.partials.analysis', ['lines' => $analysis['sections']['pengguna_alumni'] ?? []])
    @else
        <p class="muted">Belum ada data kuesioner Pengguna Alumni untuk cakupan ini.</p>
    @endif

    @if (! empty($analysis['sections']))
        @php $A = \App\Services\ReportAnalysisService::class @endphp
        <p class="muted" style="margin-top: 18px; font-size: 9px;">
            Keterangan analisis: dibuat otomatis dari angka laporan dengan kriteria internal sistem, bukan target nasional.
            Tingkat respon dan masa tunggu &le;6 bulan: sangat baik &ge;{{ array_keys($A::RESPON)[0] }}%, baik &ge;{{ array_keys($A::RESPON)[1] }}%,
            cukup &ge;{{ array_keys($A::RESPON)[2] }}%, di bawahnya kurang. Relevansi bidang studi: baik &ge;{{ array_keys($A::RELEVANSI)[0] }}%,
            cukup &ge;{{ array_keys($A::RELEVANSI)[1] }}%. Fakultas ditandai bila partisipasinya di bawah {{ $A::FAKULTAS_RENDAH }}%.
            Kesenjangan kompetensi dihitung bila kebutuhan pekerjaan melebihi penguasaan saat lulus minimal
            {{ number_format($A::KESENJANGAN_KOMPETENSI, 1, ',', '.') }} poin (skala 1&ndash;5).
            Tren membandingkan tahun lulus ini dengan hingga 4 tahun sebelumnya, dengan cakupan dan filter yang sama.
        </p>
    @endif

    <div class="footer">
        Dibuat otomatis oleh Sistem Tracer Studi UNSOED pada {{ $generatedAt->timezone(config('app.display_timezone'))->translatedFormat('d F Y H:i') }} WIB
        oleh {{ $generatedBy }}.
    </div>
</body>
</html>
