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
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN TRACER STUDY</h1>
        <div class="subtitle">Universitas Jenderal Soedirman{{ $scopeLabel ? ' — '.$scopeLabel : '' }}</div>
        <div class="subtitle">Tahun Lulus {{ $year }}</div>
    </div>

    <h2>Ringkasan Eksekutif</h2>
    @if ($current)
        <table class="stats-grid">
            <tr>
                <td width="25%">
                    <div class="stat-box">
                        <div class="stat-value">{{ number_format($current['jumlah_alumni'], 0, ',', '.') }}</div>
                        <div class="stat-label">Jumlah Lulusan</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="stat-box">
                        <div class="stat-value">{{ $current['responden'] }} ({{ $current['persentase_responden'] }}%)</div>
                        <div class="stat-label">Responden</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="stat-box">
                        <div class="stat-value">{{ $current['iku_berdasar_lulusan'] }}%</div>
                        <div class="stat-label">IKU Berdasar Lulusan</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="stat-box">
                        <div class="stat-value">Rp {{ number_format($current['rata_rata_penghasilan'], 0, ',', '.') }}</div>
                        <div class="stat-label">Rata-rata Penghasilan</div>
                    </div>
                </td>
            </tr>
        </table>
    @else
        <p class="muted">Belum ada data alumni untuk tahun lulus {{ $year }} pada cakupan ini.</p>
    @endif

    <h2>Rekap per Fakultas — Tahun Lulus {{ $year }}</h2>
    @if ($facultyRecap['rows']->count())
        <table>
            <thead>
                <tr>
                    <th>Fakultas</th>
                    <th>Jumlah Alumni</th>
                    <th>Responden</th>
                    <th>% Respon</th>
                    <th>IKU Berdasar Lulusan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($facultyRecap['rows'] as $row)
                    <tr>
                        <td>{{ $row['faculty'] }}</td>
                        <td>{{ $row['jumlah_alumni'] }}</td>
                        <td>{{ $row['responden'] }}</td>
                        <td>{{ $row['persentase_responden'] }}%</td>
                        <td>{{ $row['iku_berdasar_lulusan'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">Tidak ada rincian per fakultas untuk cakupan ini.</p>
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
