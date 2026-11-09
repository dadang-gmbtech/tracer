<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Writes the analysis in the auto-generated tracer report: an "Analisis"
 * paragraph under each section (how the figure moved over recent years, how
 * it rates against the criteria below, what to do about it), and an
 * executive summary gathering the key figures, what went well, what needs
 * attention, and the top recommendations.
 *
 * Rule-based on purpose — the same numbers always give the same wording, and
 * nothing leaves the server. The criteria are this system's own yardsticks,
 * not national targets; they're printed in the report so readers can judge.
 */
class ReportAnalysisService
{
    /** Respon rate (%): at or above => rating. */
    public const RESPON = [80 => 'sangat baik', 60 => 'baik', 40 => 'cukup'];

    /** Share who found work/business within 6 months (%). */
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

    /** A competency whose need exceeds mastery by at least this much is a gap. */
    public const KESENJANGAN_KOMPETENSI = 0.3;

    /** Below this share earning above 1.2 x UMP (of those working), flag it. */
    public const GAJI_UMP_RENDAH = 50;

    /** @var list<string> */
    private array $baik = [];

    /** @var list<array{bobot: int, teks: string, rekomendasi: string}> */
    private array $perhatian = [];

    /**
     * @param  array<int, array{current: array<string, mixed>, breakdown: array<string, mixed>}>  $history
     *                                                                                                      the report year and the years before it (oldest first), from DashboardService
     * @param  Collection<int, array<string, mixed>>  $facultyRows  DashboardService::facultyRecap() rows
     * @param  array<string, mixed>  $employer  EmployerDashboardService::summary()['total']
     * @return array{sections: array<string, list<string>>, ringkasan: array<string, list<string>>}
     */
    public function analyze(int $year, array $history, Collection $facultyRows, array $employer): array
    {
        $this->baik = [];
        $this->perhatian = [];

        // Only years that had graduates count as history to compare against.
        $history = array_filter($history, fn ($h) => ($h['current']['jumlah_alumni'] ?? 0) > 0);
        ksort($history);
        if (! isset($history[$year])) {
            return ['sections' => [], 'ringkasan' => []];
        }
        $current = $history[$year]['current'];
        $breakdown = $history[$year]['breakdown'];

        $sections = array_filter([
            'responden' => $this->responden($current, $history),
            'status' => $this->status($breakdown['status_lulusan'], $history),
            'fakultas' => $this->fakultas($facultyRows),
            'masa_tunggu' => $this->masaTunggu($breakdown['masa_tunggu'], $history),
            'pendapatan' => $this->pendapatan($breakdown, $history),
            'iku' => $this->iku($current, $breakdown['iku'], $history),
            'tempat_kerja' => $this->tempatKerja(collect($breakdown['tempat_kerja'])),
            'f14' => $this->relevansi($breakdown['f14'], $history),
            'f15' => $this->kesesuaianJenjang($breakdown['f15']),
            'jenis_instansi' => $this->jenisInstansi($breakdown['jenis_instansi']),
            'ruang_lingkup' => $this->ruangLingkup($breakdown['ruang_lingkup']),
            'cara_mencari_kerja' => $this->caraMencariKerja($breakdown['cara_mencari_kerja']),
            'kompetensi' => $this->kompetensi($breakdown['kompetensi']),
            'metode' => $this->metode($breakdown['metode_pembelajaran']),
            'pengguna_alumni' => $this->penggunaAlumni($employer),
        ]);

        return ['sections' => $sections, 'ringkasan' => $this->ringkasan($current, $breakdown)];
    }

    /** @return array<string, list<string>> */
    private function ringkasan(array $current, array $breakdown): array
    {
        $status = $breakdown['status_lulusan'];
        $statusTotal = array_sum($status);
        $masaTunggu = $breakdown['masa_tunggu'];

        $temuan = array_values(array_filter([
            "{$this->n($current['responden'])} dari {$this->n($current['jumlah_alumni'])} lulusan mengisi kuesioner (respon {$this->pct($current['persentase_responden'])}).",
            $statusTotal > 0
                ? "{$this->pct(($status['bekerja'] + $status['wiraswasta'] + $status['studi_lanjut']) / $statusTotal * 100)} responden sudah bekerja, berwirausaha, atau melanjutkan studi."
                : null,
            array_sum($masaTunggu) > 0
                ? "{$this->pct($masaTunggu['<=6 bulan'] / array_sum($masaTunggu) * 100)} mendapat pekerjaan/usaha dalam 6 bulan setelah lulus."
                : null,
            ($breakdown['rata_rata_pendapatan'] ?? 0) > 0
                ? "Rata-rata pendapatan Rp {$this->n($breakdown['rata_rata_pendapatan'])} per bulan."
                : null,
            "IKU berdasar lulusan (D3/S1) {$this->pct($current['iku_berdasar_lulusan'])}.",
        ]));

        $perhatian = collect($this->perhatian)->sortByDesc('bobot')->values();

        return [
            'temuan' => $temuan,
            'baik' => array_values(array_unique($this->baik)),
            'perhatian' => $perhatian->pluck('teks')->unique()->values()->all(),
            'rekomendasi' => $perhatian->pluck('rekomendasi')->filter()->unique()->take(5)->values()->all(),
        ];
    }

    /** @return list<string> */
    private function responden(array $current, array $history): array
    {
        $rate = (float) $current['persentase_responden'];
        $rating = $this->rate($rate, self::RESPON);
        $lines = ["Tingkat respon {$this->pct($rate)} tergolong {$rating}."];
        $lines[] = $this->trend('Tingkat respon', $this->series($history, fn ($h) => $h['current']['persentase_responden']), 'pct');

        if (in_array($rating, ['sangat baik', 'baik'], true)) {
            $this->baik[] = "Tingkat respon {$this->pct($rate)} ({$rating}).";
        }

        $lines[] = match ($rating) {
            'sangat baik' => 'Data cukup mewakili lulusan untuk dijadikan dasar evaluasi.',
            'baik' => 'Data sudah cukup mewakili, namun masih ada ruang untuk menjangkau lulusan yang belum mengisi.',
            default => 'Hasil di bagian berikutnya perlu dibaca hati-hati karena belum mewakili sebagian besar lulusan. '
                .'Disarankan pengingat berkala melalui WhatsApp/email dan melibatkan program studi untuk menghubungi alumninya.',
        };

        if (! in_array($rating, ['sangat baik', 'baik'], true)) {
            $this->perhatian[] = [
                'bobot' => $rating === 'kurang' ? 100 : 80,
                'teks' => "Tingkat respon baru {$this->pct($rate)}.",
                'rekomendasi' => 'Naikkan tingkat respon lewat pengingat berkala dan pelibatan program studi; ini cara paling langsung menaikkan IKU.',
            ];
        }

        return array_values(array_filter($lines));
    }

    /** @return list<string> */
    private function status(array $status, array $history): array
    {
        $total = array_sum($status);
        if ($total === 0) {
            return [];
        }

        $terserap = $status['bekerja'] + $status['wiraswasta'] + $status['studi_lanjut'];
        $belumPct = $status['belum_bekerja'] / $total * 100;

        $lines = [
            "Sebanyak {$this->n($terserap)} responden ({$this->pct($terserap / $total * 100)}) sudah bekerja, berwirausaha, atau melanjutkan studi.",
            $this->trend('Proporsi lulusan yang sudah beraktivitas', $this->series($history, function ($h) {
                $s = $h['breakdown']['status_lulusan'];
                $t = array_sum($s);

                return $t > 0 ? ($s['bekerja'] + $s['wiraswasta'] + $s['studi_lanjut']) / $t * 100 : null;
            }), 'pct'),
        ];

        if ($belumPct > self::BELUM_BEKERJA_TINGGI) {
            $lines[] = "Proporsi yang belum bekerja/masih mencari kerja cukup tinggi ({$this->pct($belumPct)}). "
                .'Layanan karier seperti bursa kerja, informasi lowongan, dan pelatihan persiapan kerja perlu diperkuat.';
            $this->perhatian[] = [
                'bobot' => 55,
                'teks' => "{$this->pct($belumPct)} responden belum bekerja atau masih mencari kerja.",
                'rekomendasi' => 'Perkuat layanan karier: bursa kerja, informasi lowongan, dan pelatihan persiapan kerja.',
            ];
        }

        $wiraPct = $status['wiraswasta'] / $total * 100;
        if ($wiraPct >= 10) {
            $lines[] = "Minat berwirausaha cukup menonjol ({$this->pct($wiraPct)}) dan layak didukung dengan program inkubasi bisnis.";
            $this->baik[] = "Minat berwirausaha menonjol ({$this->pct($wiraPct)} responden).";
        }

        return array_values(array_filter($lines));
    }

    /** @return list<string> */
    private function fakultas(Collection $rows): array
    {
        if ($rows->count() < 2) {
            return [];
        }

        $gap = $rows->max('persentase_responden') - $rows->min('persentase_responden');
        $low = $rows->filter(fn ($r) => $r['persentase_responden'] < self::FAKULTAS_RENDAH)->sortBy('persentase_responden');

        $lines = ["Selisih partisipasi antara fakultas tertinggi dan terendah {$this->num($gap)} poin persentase."
            .($gap > 30 ? ' Ketimpangan ini cukup besar.' : ' Partisipasi relatif merata.')];

        if ($low->isNotEmpty()) {
            $names = $low->map(fn ($r) => $r['faculty'].' ('.$this->pct($r['persentase_responden']).')')->join(', ');
            $lines[] = 'Fakultas dengan partisipasi di bawah '.self::FAKULTAS_RENDAH."%: {$names}. "
                .'Fakultas tersebut perlu pendampingan khusus untuk menjangkau alumninya.';
            $this->perhatian[] = [
                'bobot' => 60,
                'teks' => "{$low->count()} fakultas dengan partisipasi di bawah ".self::FAKULTAS_RENDAH."%: {$low->pluck('faculty')->join(', ')}.",
                'rekomendasi' => "Dampingi fakultas dengan partisipasi rendah ({$low->pluck('faculty')->join(', ')}) untuk menjangkau alumninya.",
            ];
        }

        return $lines;
    }

    /** @return list<string> */
    private function masaTunggu(array $masaTunggu, array $history): array
    {
        $total = array_sum($masaTunggu);
        if ($total === 0) {
            return [];
        }

        $cepat = $masaTunggu['<=6 bulan'] / $total * 100;
        $rating = $this->rate($cepat, self::MASA_TUNGGU);
        $lines = [
            "Lulusan yang mendapat pekerjaan/usaha dalam 6 bulan sebesar {$this->pct($cepat)}, tergolong {$rating}.",
            $this->trend('Proporsi masa tunggu ≤6 bulan', $this->series($history, function ($h) {
                $m = $h['breakdown']['masa_tunggu'];

                return array_sum($m) > 0 ? $m['<=6 bulan'] / array_sum($m) * 100 : null;
            }), 'pct'),
        ];

        if ($masaTunggu['>12 bulan'] > 0) {
            $lines[] = "{$this->n($masaTunggu['>12 bulan'])} lulusan membutuhkan lebih dari 12 bulan; lulusan ini tidak dihitung dalam IKU.";
        }

        if (in_array($rating, ['sangat baik', 'baik'], true)) {
            $this->baik[] = "Masa tunggu ≤6 bulan {$this->pct($cepat)} ({$rating}).";
        } else {
            $lines[] = 'Disarankan memperkuat magang, kerja sama dengan industri, dan penyaluran kerja sebelum wisuda.';
            $this->perhatian[] = [
                'bobot' => 50,
                'teks' => "Baru {$this->pct($cepat)} lulusan mendapat pekerjaan dalam 6 bulan.",
                'rekomendasi' => 'Perkuat magang, kerja sama industri, dan penyaluran kerja sebelum wisuda untuk memperpendek masa tunggu.',
            ];
        }

        return array_values(array_filter($lines));
    }

    /** @return list<string> */
    private function pendapatan(array $breakdown, array $history): array
    {
        $buckets = $breakdown['pendapatan'];
        $total = array_sum($buckets);
        if ($total === 0) {
            return [];
        }

        $avg = (float) $breakdown['rata_rata_pendapatan'];
        $atas5 = ($buckets['5-10 juta'] + $buckets['>10 juta']) / $total * 100;
        $bawah3 = $buckets['<3 juta'] / $total * 100;

        $lines = [
            "Rata-rata pendapatan lulusan Rp {$this->n($avg)} per bulan.",
            $this->trend('Rata-rata pendapatan', $this->series($history, fn ($h) => ($h['breakdown']['rata_rata_pendapatan'] ?? 0) ?: null), 'rp'),
            "{$this->pct($atas5)} berpendapatan di atas 5 juta, sedangkan {$this->pct($bawah3)} di bawah 3 juta.",
        ];

        if ($bawah3 > 30) {
            $lines[] = 'Porsi pendapatan di bawah 3 juta cukup besar; perlu dicek apakah sejalan dengan UMP provinsi tempat kerja.';
        }

        return array_values(array_filter($lines));
    }

    /** @return list<string> */
    private function iku(array $current, array $iku, array $history): array
    {
        $lines = [
            "IKU berdasar lulusan {$this->pct($current['iku_berdasar_lulusan'])}, dari total bobot {$this->num($iku['bobot_total'], 2)} "
            ."atas {$this->n($iku['lulusan'])} lulusan D3/S1.",
            $this->trend('IKU berdasar lulusan', $this->series($history, fn ($h) => $h['current']['iku_berdasar_lulusan']), 'pct'),
        ];

        if ($iku['lulusan'] === 0) {
            return array_values(array_filter($lines));
        }

        $komponen = $iku['komponen'];
        $kontribusi = collect(['bekerja' => 'bekerja', 'wiraswasta' => 'wiraswasta', 'studi_lanjut' => 'studi lanjut'])
            ->filter(fn ($label, $key) => $komponen[$key]['jumlah'] > 0)
            ->map(fn ($label, $key) => "{$label} {$this->num($komponen[$key]['bobot'], 2)} ({$this->n($komponen[$key]['jumlah'])} orang)")
            ->join(', ');
        if ($kontribusi !== '') {
            $lines[] = "Bobot terbesar berasal dari: {$kontribusi}.";
        }

        // What holds the score down, largest first.
        $tidakMengisiPct = $iku['tidak_mengisi'] / $iku['lulusan'] * 100;
        $penekan = [];
        if ($iku['tidak_mengisi'] > 0) {
            $penekan[] = "{$this->n($iku['tidak_mengisi'])} lulusan ({$this->pct($tidakMengisiPct)}) belum mengisi sehingga bernilai 0";
        }
        if ($iku['masa_tunggu_tidak_dihitung'] > 0) {
            $penekan[] = "{$this->n($iku['masa_tunggu_tidak_dihitung'])} yang bekerja/berwirausaha bermasa tunggu ≥12 bulan atau tidak mengisinya sehingga bernilai 0";
        }
        if ($penekan) {
            $lines[] = 'Faktor yang menekan IKU: '.implode('; ', $penekan).'.';
        }

        if ($iku['bekerja_wiraswasta'] > 0) {
            $denganUmp = $iku['bekerja_wiraswasta'] - $iku['tanpa_data_ump'];
            $atasUmp = $denganUmp > 0 ? $iku['gaji_di_atas_ump'] / $denganUmp * 100 : 0;
            $lines[] = "{$this->pct($atasUmp)} lulusan yang bekerja/berwirausaha bergaji di atas 1,2 × UMP provinsinya; "
                .'yang di bawahnya mendapat bobot lebih rendah.';

            if ($iku['tanpa_data_ump'] > 0) {
                $lines[] = "{$this->n($iku['tanpa_data_ump'])} lulusan bekerja di provinsi yang belum punya data UMP tahun ini, "
                    .'sehingga gajinya dianggap di bawah 1,2 × UMP. Lengkapi data UMP agar IKU terhitung akurat.';
                $this->perhatian[] = [
                    'bobot' => 65,
                    'teks' => "{$this->n($iku['tanpa_data_ump'])} lulusan bekerja di provinsi tanpa data UMP, sehingga IKU-nya terhitung lebih rendah.",
                    'rekomendasi' => 'Lengkapi data UMP per provinsi untuk tahun ini di menu Master Data agar IKU terhitung akurat.',
                ];
            }

            if ($denganUmp > 0 && $atasUmp < self::GAJI_UMP_RENDAH) {
                $this->perhatian[] = [
                    'bobot' => 40,
                    'teks' => "Hanya {$this->pct($atasUmp)} lulusan yang bekerja bergaji di atas 1,2 × UMP.",
                    'rekomendasi' => 'Dorong lulusan ke sektor dan jabatan dengan pendapatan lebih baik melalui pembekalan karier dan jejaring alumni.',
                ];
            }
        }

        if ($tidakMengisiPct > 40) {
            $this->perhatian[] = [
                'bobot' => 90,
                'teks' => "{$this->pct($tidakMengisiPct)} lulusan D3/S1 belum mengisi kuesioner dan bernilai 0 dalam IKU.",
                'rekomendasi' => 'Naikkan tingkat respon lewat pengingat berkala dan pelibatan program studi; ini cara paling langsung menaikkan IKU.',
            ];
        }

        return array_values(array_filter($lines));
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

        return [
            $topPct > self::KONSENTRASI_WILAYAH
                ? "Lulusan terkonsentrasi di {$top['name']} ({$this->pct($topPct)})."
                : "Sebaran lulusan cukup merata; {$top['name']} sebagai provinsi terbanyak menampung {$this->pct($topPct)}.",
            "Lulusan bekerja di {$rows->count()} provinsi/wilayah".($luarNegeri > 0 ? ", termasuk {$this->n($luarNegeri)} orang di luar negeri." : '.'),
        ];
    }

    /** @return list<string> */
    private function relevansi(array $f14, array $history): array
    {
        $total = array_sum($f14);
        if ($total === 0) {
            return [];
        }

        $relevan = ($f14['Sangat relevan'] + $f14['Relevan']) / $total * 100;
        $rating = $this->rate($relevan, self::RELEVANSI);
        $lines = [
            "{$this->pct($relevan)} lulusan menilai pekerjaannya relevan atau sangat relevan dengan bidang studi, tergolong {$rating}.",
            $this->trend('Tingkat relevansi', $this->series($history, function ($h) {
                $f = $h['breakdown']['f14'];

                return array_sum($f) > 0 ? ($f['Sangat relevan'] + $f['Relevan']) / array_sum($f) * 100 : null;
            }), 'pct'),
        ];

        if ($rating === 'baik') {
            $this->baik[] = "{$this->pct($relevan)} lulusan menilai pekerjaannya relevan dengan bidang studi.";
        } else {
            $lines[] = 'Kurikulum perlu ditinjau bersama pengguna lulusan agar lebih sesuai dengan kebutuhan dunia kerja.';
            $this->perhatian[] = [
                'bobot' => 45,
                'teks' => "Relevansi pekerjaan dengan bidang studi baru {$this->pct($relevan)}.",
                'rekomendasi' => 'Tinjau kurikulum bersama pengguna lulusan agar lebih sesuai dengan kebutuhan dunia kerja.',
            ];
        }

        return array_values(array_filter($lines));
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

        if ($dibawah > self::UNDEREMPLOYMENT_TINGGI) {
            $lines[] = "Sebanyak {$this->pct($dibawah)} bekerja di bawah jenjang pendidikannya (underemployment), perlu menjadi perhatian.";
            $this->perhatian[] = [
                'bobot' => 40,
                'teks' => "{$this->pct($dibawah)} lulusan bekerja di bawah jenjang pendidikannya.",
                'rekomendasi' => null,
            ];
        } else {
            $lines[] = "Hanya {$this->pct($dibawah)} yang bekerja di bawah jenjang pendidikannya.";
        }

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

        if ($kampus < self::KANAL_KAMPUS_RENDAH) {
            $lines[] = "Peran kampus sebagai saluran informasi kerja masih kecil ({$this->pct($kampus)}); pusat karier perlu lebih aktif membagikan lowongan dan menjalin kerja sama dengan perusahaan.";
            $this->perhatian[] = [
                'bobot' => 35,
                'teks' => "Hanya {$this->pct($kampus)} lulusan mendapat informasi kerja dari kampus.",
                'rekomendasi' => 'Aktifkan pusat karier: bagikan lowongan, jalin kerja sama perusahaan, dan manfaatkan jejaring alumni.',
            ];
        } else {
            $lines[] = "Kampus berperan sebagai saluran informasi kerja bagi {$this->pct($kampus)} lulusan.";
        }

        return $lines;
    }

    /** @return list<string> */
    private function kompetensi(array $kompetensi): array
    {
        $rows = collect($kompetensi);
        if ($rows->isEmpty()) {
            return [];
        }

        $kurang = $rows->filter(fn ($k) => $k['selisih'] >= self::KESENJANGAN_KOMPETENSI)->sortByDesc('selisih');
        $lebih = $rows->filter(fn ($k) => $k['selisih'] <= -self::KESENJANGAN_KOMPETENSI)->sortBy('selisih');
        $terkuat = $rows->sortByDesc('saat_lulus')->first();

        $lines = ["Kompetensi yang paling dikuasai lulusan saat lulus adalah {$this->lower($terkuat['label'])} (rata-rata {$this->num($terkuat['saat_lulus'], 2)} dari 5)."];

        if ($kurang->isNotEmpty()) {
            $daftar = $kurang->map(fn ($k) => "{$this->lower($k['label'])} (+{$this->num($k['selisih'], 2)})")->join(', ');
            $lines[] = "Kompetensi yang lebih dibutuhkan pekerjaan daripada yang dikuasai saat lulus: {$daftar}. Ini menjadi prioritas penguatan pembelajaran.";
            $this->perhatian[] = [
                'bobot' => 30,
                'teks' => "Kesenjangan kompetensi terbesar: {$this->lower($kurang->first()['label'])}.",
                'rekomendasi' => "Perkuat {$kurang->take(3)->pluck('label')->map(fn ($l) => $this->lower($l))->join(', ')} dalam pembelajaran karena paling dibutuhkan pekerjaan dibanding yang dikuasai saat lulus.",
            ];
        } else {
            $lines[] = 'Tidak ada kesenjangan berarti antara kompetensi yang dikuasai saat lulus dan yang dibutuhkan pekerjaan.';
            $this->baik[] = 'Kompetensi lulusan saat lulus sudah sejalan dengan yang dibutuhkan pekerjaan.';
        }

        if ($lebih->isNotEmpty()) {
            $lines[] = 'Kompetensi yang dikuasai melebihi kebutuhan pekerjaan: '
                .$lebih->map(fn ($k) => $this->lower($k['label']))->join(', ').'.';
        }

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

        $this->baik[] = "Pengguna lulusan paling menghargai {$this->lower($highest['label'])} ({$this->pct($highest['indeks'])}).";
        $this->perhatian[] = [
            'bobot' => 25,
            'teks' => "Kompetensi dengan penilaian pengguna lulusan terendah: {$this->lower($lowest['label'])} ({$this->pct($lowest['indeks'])}).",
            'rekomendasi' => "Jadikan {$this->lower($lowest['label'])} prioritas pengembangan, karena paling rendah dinilai pengguna lulusan.",
        ];

        return [
            "Kompetensi yang paling dihargai pengguna lulusan adalah {$this->lower($highest['label'])} ({$this->pct($highest['indeks'])}), "
            ."sedangkan yang terendah {$this->lower($lowest['label'])} ({$this->pct($lowest['indeks'])}).",
            "Penguatan {$this->lower($lowest['label'])} dapat menjadi prioritas pengembangan kurikulum dan kegiatan pendukung.",
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @return array<int, float> year => value, for years where it could be computed
     */
    private function series(array $history, callable $value): array
    {
        $series = [];
        foreach ($history as $year => $h) {
            $v = $value($h);
            if ($v !== null) {
                $series[$year] = (float) $v;
            }
        }

        return $series;
    }

    /**
     * One sentence on where a figure has been heading: a streak of rises or
     * falls into the latest year, otherwise its range — or, with just two
     * years, the change from the year before.
     *
     * @param  array<int, float>  $series  year => value, oldest first, ending with the report year
     */
    private function trend(string $what, array $series, string $unit): ?string
    {
        if (count($series) < 2) {
            return null;
        }

        $fmt = fn (float $v) => $unit === 'rp' ? 'Rp '.$this->n($v) : $this->pct($v);
        $years = array_keys($series);
        $values = array_values($series);
        $last = count($values) - 1;
        $now = $values[$last];
        $before = $values[$last - 1];
        $prevYear = $years[$last - 1];

        // Change from the year before; percentages in points, money in percent.
        $delta = $unit === 'rp' ? ($before > 0 ? ($now - $before) / $before * 100 : 0) : $now - $before;
        $flat = abs($delta) < 1;
        $change = $flat
            ? "relatif sama dengan {$prevYear} ({$fmt($before)})"
            : ($delta > 0 ? 'naik ' : 'turun ').($unit === 'rp' ? $this->pct(abs($delta)) : $this->num(abs($delta)).' poin')
                ." dibanding {$prevYear} ({$fmt($before)})";

        if (count($series) === 2) {
            return "{$what} {$change}.";
        }

        // Length of the run of same-direction moves ending at the report year.
        $streak = 0;
        $direction = 0;
        for ($i = $last; $i > 0; $i--) {
            $step = $values[$i] - $values[$i - 1];
            $sign = abs($step) < ($unit === 'rp' ? $values[$i - 1] * 0.01 : 0.5) ? 0 : ($step > 0 ? 1 : -1);
            if ($sign === 0 || ($direction !== 0 && $sign !== $direction)) {
                break;
            }
            $direction = $sign;
            $streak++;
        }

        if ($streak >= 2) {
            $from = $last - $streak;

            return "{$what} {$change}, dan ".($direction > 0 ? 'naik' : 'turun')." {$streak} tahun berturut-turut "
                ."dari {$fmt($values[$from])} ({$years[$from]}).";
        }

        $min = array_search(min($values), $values, true);
        $max = array_search(max($values), $values, true);

        return "{$what} {$change}. Dalam ".count($series)." tahun terakhir angkanya berkisar antara {$fmt($values[$min])} ({$years[$min]}) "
            ."dan {$fmt($values[$max])} ({$years[$max]}).";
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
