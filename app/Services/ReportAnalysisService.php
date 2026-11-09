<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Writes the "Analisis" paragraph under each section of the auto-generated
 * tracer report: how the figure compares with the previous graduation year,
 * how it rates against the criteria below, and what to do about it.
 *
 * Rule-based on purpose — the same numbers always give the same wording, and
 * nothing leaves the server. The criteria are this system's own yardsticks,
 * not national targets; they're listed in the report so readers can judge.
 */
class ReportAnalysisService
{
    /** Respon rate (%): at or above => rating. */
    public const RESPON = [80 => 'sangat baik', 60 => 'baik', 40 => 'cukup'];

    /** Share of respondents who found work/business within 6 months (%). */
    public const MASA_TUNGGU = [80 => 'sangat baik', 60 => 'baik', 40 => 'cukup'];

    /** Share rating their job "sangat relevan" or "relevan" to their study (%). */
    public const RELEVANSI = [70 => 'baik', 50 => 'cukup'];

    /** A faculty below this respon rate is flagged. */
    public const FAKULTAS_RENDAH = 50;

    /** Above this share of belum bekerja/mencari kerja respondents, flag it. */
    public const BELUM_BEKERJA_TINGGI = 20;

    /** Above this share working below their education level, flag it. */
    public const UNDEREMPLOYMENT_TINGGI = 20;

    /** Below this share finding work through the campus, flag it. */
    public const KANAL_KAMPUS_RENDAH = 10;

    /** A province holding more than this share counts as concentrated. */
    public const KONSENTRASI_WILAYAH = 50;

    /**
     * @param  array<string, mixed>  $current  DashboardService aggregate for the year
     * @param  array<string, mixed>|null  $previous  the same for the year before, if any
     * @param  array<string, mixed>  $breakdown  DashboardService::reportBreakdown() for the year
     * @param  array<string, mixed>|null  $previousBreakdown  the same for the year before
     * @param  Collection<int, array<string, mixed>>  $facultyRows  DashboardService::facultyRecap() rows
     * @param  array<string, mixed>  $employer  EmployerDashboardService::summary()['total']
     * @return array<string, list<string>> section key => sentences
     */
    public function analyze(
        int $year,
        array $current,
        ?array $previous,
        array $breakdown,
        ?array $previousBreakdown,
        Collection $facultyRows,
        array $employer,
    ): array {
        $prevYear = $year - 1;
        $hasPrevious = $previous !== null && ($previous['jumlah_alumni'] ?? 0) > 0;
        $previousBreakdown = $hasPrevious ? $previousBreakdown : null;

        return array_filter([
            'responden' => $this->responden($current, $hasPrevious ? $previous : null, $prevYear),
            'status' => $this->status($breakdown['status_lulusan'], $previousBreakdown['status_lulusan'] ?? null, $prevYear),
            'fakultas' => $this->fakultas($facultyRows),
            'masa_tunggu' => $this->masaTunggu($breakdown['masa_tunggu'], $previousBreakdown['masa_tunggu'] ?? null, $prevYear),
            'pendapatan' => $this->pendapatan($breakdown, $previousBreakdown, $prevYear),
            'tempat_kerja' => $this->tempatKerja(collect($breakdown['tempat_kerja'])),
            'f14' => $this->relevansi($breakdown['f14'], $previousBreakdown['f14'] ?? null, $prevYear),
            'f15' => $this->kesesuaianJenjang($breakdown['f15']),
            'jenis_instansi' => $this->jenisInstansi($breakdown['jenis_instansi']),
            'ruang_lingkup' => $this->ruangLingkup($breakdown['ruang_lingkup']),
            'cara_mencari_kerja' => $this->caraMencariKerja($breakdown['cara_mencari_kerja']),
            'metode' => $this->metode($breakdown['metode_pembelajaran']),
            'iku' => $this->iku($current, $hasPrevious ? $previous : null, $prevYear),
            'pengguna_alumni' => $this->penggunaAlumni($employer),
        ]);
    }

    /** @return list<string> */
    private function responden(array $current, ?array $previous, int $prevYear): array
    {
        if (($current['jumlah_alumni'] ?? 0) === 0) {
            return [];
        }

        $rate = (float) $current['persentase_responden'];
        $rating = $this->rate($rate, self::RESPON);
        $lines = ["Tingkat respon {$this->pct($rate)} tergolong {$rating}."];

        if ($previous) {
            $lines[] = $this->trend('Tingkat respon', $rate, (float) $previous['persentase_responden'], $prevYear);
        }

        $lines[] = match ($rating) {
            'sangat baik' => 'Data cukup mewakili lulusan untuk dijadikan dasar evaluasi.',
            'baik' => 'Data sudah cukup mewakili, namun masih ada ruang untuk menjangkau lulusan yang belum mengisi.',
            default => 'Hasil di bagian berikutnya perlu dibaca hati-hati karena belum mewakili sebagian besar lulusan. '
                .'Disarankan pengingat berkala melalui WhatsApp/email dan melibatkan program studi untuk menghubungi alumninya.',
        };

        return $lines;
    }

    /** @return list<string> */
    private function status(array $status, ?array $previous, int $prevYear): array
    {
        $total = array_sum($status);
        if ($total === 0) {
            return [];
        }

        $terserap = $status['bekerja'] + $status['wiraswasta'] + $status['studi_lanjut'];
        $terserapPct = $terserap / $total * 100;
        $belumPct = $status['belum_bekerja'] / $total * 100;

        $lines = ["Sebanyak {$this->n($terserap)} responden ({$this->pct($terserapPct)}) sudah bekerja, berwirausaha, atau melanjutkan studi."];

        if ($previous && array_sum($previous) > 0) {
            $prevPct = ($previous['bekerja'] + $previous['wiraswasta'] + $previous['studi_lanjut']) / array_sum($previous) * 100;
            $lines[] = $this->trend('Proporsi lulusan yang sudah beraktivitas', $terserapPct, $prevPct, $prevYear);
        }

        if ($belumPct > self::BELUM_BEKERJA_TINGGI) {
            $lines[] = "Proporsi yang belum bekerja/masih mencari kerja cukup tinggi ({$this->pct($belumPct)}). "
                .'Layanan karier seperti bursa kerja, informasi lowongan, dan pelatihan persiapan kerja perlu diperkuat.';
        }

        $wiraPct = $status['wiraswasta'] / $total * 100;
        if ($wiraPct >= 10) {
            $lines[] = "Minat berwirausaha cukup menonjol ({$this->pct($wiraPct)}) dan layak didukung dengan program inkubasi bisnis.";
        }

        return $lines;
    }

    /** @return list<string> */
    private function fakultas(Collection $rows): array
    {
        if ($rows->count() < 2) {
            return [];
        }

        $max = $rows->max('persentase_responden');
        $min = $rows->min('persentase_responden');
        $gap = $max - $min;
        $low = $rows->filter(fn ($r) => $r['persentase_responden'] < self::FAKULTAS_RENDAH)->sortBy('persentase_responden');

        $lines = ["Selisih partisipasi antara fakultas tertinggi dan terendah {$this->num($gap)} poin persentase."
            .($gap > 30 ? ' Ketimpangan ini cukup besar.' : ' Partisipasi relatif merata.')];

        if ($low->isNotEmpty()) {
            $names = $low->map(fn ($r) => $r['faculty'].' ('.$this->pct($r['persentase_responden']).')')->join(', ');
            $lines[] = 'Fakultas dengan partisipasi di bawah '.self::FAKULTAS_RENDAH."%: {$names}. "
                .'Fakultas tersebut perlu pendampingan khusus untuk menjangkau alumninya.';
        }

        return $lines;
    }

    /** @return list<string> */
    private function masaTunggu(array $masaTunggu, ?array $previous, int $prevYear): array
    {
        $total = array_sum($masaTunggu);
        if ($total === 0) {
            return [];
        }

        $cepat = $masaTunggu['<=6 bulan'] / $total * 100;
        $rating = $this->rate($cepat, self::MASA_TUNGGU);
        $lines = ["Lulusan yang mendapat pekerjaan/usaha dalam 6 bulan sebesar {$this->pct($cepat)}, tergolong {$rating}."];

        if ($previous && array_sum($previous) > 0) {
            $lines[] = $this->trend('Proporsi masa tunggu ≤6 bulan', $cepat, $previous['<=6 bulan'] / array_sum($previous) * 100, $prevYear);
        }

        if ($masaTunggu['>12 bulan'] > 0) {
            $lines[] = "{$this->n($masaTunggu['>12 bulan'])} lulusan membutuhkan lebih dari 12 bulan; lulusan ini tidak dihitung dalam IKU.";
        }

        if (in_array($rating, ['cukup', 'kurang'], true)) {
            $lines[] = 'Disarankan memperkuat magang, kerja sama dengan industri, dan penyaluran kerja sebelum wisuda.';
        }

        return $lines;
    }

    /** @return list<string> */
    private function pendapatan(array $breakdown, ?array $previous, int $prevYear): array
    {
        $buckets = $breakdown['pendapatan'];
        $total = array_sum($buckets);
        if ($total === 0) {
            return [];
        }

        $avg = (float) $breakdown['rata_rata_pendapatan'];
        $lines = ["Rata-rata pendapatan lulusan Rp {$this->n($avg)} per bulan."];

        $prevAvg = (float) ($previous['rata_rata_pendapatan'] ?? 0);
        if ($prevAvg > 0) {
            $change = ($avg - $prevAvg) / $prevAvg * 100;
            $lines[] = abs($change) < 1
                ? "Relatif sama dengan {$prevYear} (Rp {$this->n($prevAvg)})."
                : ($change > 0 ? 'Naik' : 'Turun')." {$this->pct(abs($change))} dibanding {$prevYear} (Rp {$this->n($prevAvg)}).";
        }

        $atas5 = ($buckets['5-10 juta'] + $buckets['>10 juta']) / $total * 100;
        $bawah3 = $buckets['<3 juta'] / $total * 100;
        $lines[] = "{$this->pct($atas5)} berpendapatan di atas 5 juta, sedangkan {$this->pct($bawah3)} di bawah 3 juta.";

        if ($bawah3 > 30) {
            $lines[] = 'Porsi pendapatan di bawah 3 juta cukup besar; perlu dicek apakah sejalan dengan UMP provinsi tempat kerja.';
        }

        return $lines;
    }

    /** @return list<string> */
    private function tempatKerja(Collection $rows): array
    {
        $total = $rows->sum('jumlah');
        if ($total === 0) {
            return [];
        }

        $top = $rows->first();
        $topPct = $top['jumlah'] / $total * 100;
        $luarNegeri = (int) ($rows->firstWhere('is_luar_negeri', true)['jumlah'] ?? 0);

        $lines = [$topPct > self::KONSENTRASI_WILAYAH
            ? "Lulusan terkonsentrasi di {$top['name']} ({$this->pct($topPct)})."
            : "Sebaran lulusan cukup merata; {$top['name']} sebagai provinsi terbanyak menampung {$this->pct($topPct)}."];

        $lines[] = "Lulusan bekerja di {$rows->count()} provinsi/wilayah"
            .($luarNegeri > 0 ? ", termasuk {$this->n($luarNegeri)} orang di luar negeri." : '.');

        return $lines;
    }

    /** @return list<string> */
    private function relevansi(array $f14, ?array $previous, int $prevYear): array
    {
        $total = array_sum($f14);
        if ($total === 0) {
            return [];
        }

        $relevan = ($f14['Sangat relevan'] + $f14['Relevan']) / $total * 100;
        $rating = $this->rate($relevan, self::RELEVANSI);
        $lines = ["{$this->pct($relevan)} lulusan menilai pekerjaannya relevan atau sangat relevan dengan bidang studi, tergolong {$rating}."];

        if ($previous && array_sum($previous) > 0) {
            $lines[] = $this->trend('Tingkat relevansi', $relevan, ($previous['Sangat relevan'] + $previous['Relevan']) / array_sum($previous) * 100, $prevYear);
        }

        if ($rating !== 'baik') {
            $lines[] = 'Kurikulum perlu ditinjau bersama pengguna lulusan agar lebih sesuai dengan kebutuhan dunia kerja.';
        }

        return $lines;
    }

    /** @return list<string> */
    private function kesesuaianJenjang(array $f15): array
    {
        $total = array_sum($f15);
        if ($total === 0) {
            return [];
        }

        $sesuai = ($f15['Tingkat yang sama'] + $f15['Setingkat lebih tinggi']) / $total * 100;
        $dibawah = ($f15['Setingkat lebih rendah'] + $f15['Tidak perlu pendidikan tinggi']) / $total * 100;

        $lines = ["{$this->pct($sesuai)} lulusan bekerja pada posisi yang setara atau lebih tinggi dari jenjang pendidikannya."];

        $lines[] = $dibawah > self::UNDEREMPLOYMENT_TINGGI
            ? "Sebanyak {$this->pct($dibawah)} bekerja di bawah jenjang pendidikannya (underemployment), perlu menjadi perhatian."
            : "Hanya {$this->pct($dibawah)} yang bekerja di bawah jenjang pendidikannya.";

        return $lines;
    }

    /** @return list<string> */
    private function jenisInstansi(array $jenis): array
    {
        $total = array_sum($jenis);
        if ($total === 0) {
            return [];
        }

        $top = array_key_first($jenis);
        $publik = (($jenis['Instansi/lembaga pemerintah'] ?? 0) + ($jenis['BUMN/BUMD'] ?? 0)) / $total * 100;

        return [
            "Sektor terbanyak yang menyerap lulusan adalah {$this->lower($top)} ({$this->pct($jenis[$top] / $total * 100)}).",
            "Sektor pemerintah dan BUMN/BUMD menyerap {$this->pct($publik)} lulusan yang bekerja.",
        ];
    }

    /** @return list<string> */
    private function ruangLingkup(array $lingkup): array
    {
        $total = array_sum($lingkup);
        if ($total === 0) {
            return [];
        }

        $luas = ($lingkup['Nasional'] + $lingkup['Multinasional/Internasional']) / $total * 100;
        $multi = $lingkup['Multinasional/Internasional'] / $total * 100;

        return ["{$this->pct($luas)} lulusan bekerja di tempat berlingkup nasional atau multinasional, "
            ."dengan {$this->pct($multi)} di lingkup multinasional/internasional."];
    }

    /** @return list<string> */
    private function caraMencariKerja(array $cara): array
    {
        if ($cara['basis'] === 0) {
            return [];
        }

        $items = $cara['items'];
        $top = array_key_first($items);
        $kampus = $this->percentOf(
            ($items['Info karir dari fakultas/universitas']['jumlah'] ?? 0) + ($items['Kantor kemahasiswaan/alumni']['jumlah'] ?? 0),
            $cara['basis'],
        );

        $lines = ["Saluran paling banyak dipakai adalah {$this->lower($top)} ({$this->pct($items[$top]['persen'])})."];

        $lines[] = $kampus < self::KANAL_KAMPUS_RENDAH
            ? "Peran kampus sebagai saluran informasi kerja masih kecil ({$this->pct($kampus)}); pusat karier perlu lebih aktif membagikan lowongan dan menjalin kerja sama dengan perusahaan."
            : "Kampus berperan sebagai saluran informasi kerja bagi {$this->pct($kampus)} lulusan.";

        return $lines;
    }

    /** @return list<string> */
    private function metode(array $metode): array
    {
        $rows = collect($metode)->sortBy('rata_rata')->values();
        if ($rows->count() < 2) {
            return [];
        }

        $most = $rows->first();
        $least = $rows->last();

        return [
            "Metode yang paling ditekankan adalah {$this->lower($most['label'])} (rata-rata {$this->num($most['rata_rata'], 2)}), "
            ."sedangkan yang paling kurang ditekankan adalah {$this->lower($least['label'])} ({$this->num($least['rata_rata'], 2)}).",
            'Metode dengan penekanan rendah dapat dievaluasi apakah perlu diperkuat sesuai capaian pembelajaran program studi.',
        ];
    }

    /** @return list<string> */
    private function iku(array $current, ?array $previous, int $prevYear): array
    {
        $iku = (float) $current['iku_berdasar_lulusan'];
        $lines = ["IKU berdasar lulusan tahun ini {$this->pct($iku)}."];

        if ($previous) {
            $lines[] = $this->trend('IKU berdasar lulusan', $iku, (float) $previous['iku_berdasar_lulusan'], $prevYear);
        }

        if ((float) $current['persentase_responden'] < 60) {
            $lines[] = 'Karena IKU dihitung terhadap seluruh lulusan, tingkat respon yang rendah ikut menekan nilai IKU; '
                .'menaikkan respon adalah cara paling langsung untuk meningkatkannya.';
        }

        return $lines;
    }

    /** @return list<string> */
    private function penggunaAlumni(array $employer): array
    {
        if (($employer['jumlah_respon'] ?? 0) === 0) {
            return [];
        }

        $questions = collect($employer['per_pertanyaan'])->filter(fn ($q) => $q['rata_rata'] !== null)->sortBy('indeks');
        if ($questions->isEmpty()) {
            return [];
        }

        $lowest = $questions->first();
        $highest = $questions->last();

        return [
            "Kompetensi yang paling dihargai pengguna lulusan adalah {$this->lower($highest['label'])} ({$this->pct($highest['indeks'])}), "
            ."sedangkan yang terendah {$this->lower($lowest['label'])} ({$this->pct($lowest['indeks'])}).",
            "Penguatan {$this->lower($lowest['label'])} dapat menjadi prioritas pengembangan kurikulum dan kegiatan pendukung.",
        ];
    }

    private function trend(string $what, float $now, float $before, int $prevYear): string
    {
        $delta = $now - $before;

        if (abs($delta) < 1) {
            return "{$what} relatif sama dengan {$prevYear} ({$this->pct($before)}).";
        }

        return "{$what} ".($delta > 0 ? 'naik' : 'turun')." {$this->num(abs($delta))} poin dibanding {$prevYear} ({$this->pct($before)}).";
    }

    /** @param  array<int, string>  $scale */
    private function rate(float $value, array $scale): string
    {
        foreach ($scale as $min => $label) {
            if ($value >= $min) {
                return $label;
            }
        }

        return 'kurang';
    }

    private function percentOf(int|float $part, int|float $total): float
    {
        return $total > 0 ? $part / $total * 100 : 0.0;
    }

    private function n(int|float $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    private function num(int|float $value, int $decimals = 1): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }

    private function pct(int|float $value): string
    {
        return $this->num($value).'%';
    }

    private function lower(string $label): string
    {
        return mb_strtolower($label);
    }
}
