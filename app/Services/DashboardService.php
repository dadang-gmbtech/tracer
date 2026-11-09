<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\Province;
use App\Models\TracerResponse;
use App\Models\UmpSalary;
use App\Models\User;
use App\Support\ProvinceCentroids;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * IKU-2 only covers D1/D2/D3/D4-Sarjana Terapan/S1 graduates (see
     * Rumus IKU.pdf) — S2/S3 alumni are excluded from every IKU percentage
     * and bobot figure, regardless of which jenjang filter is applied.
     */
    private const IKU_ELIGIBLE_LEVELS = ['D3', 'S1'];

    /** @var Collection<int, Alumni>|null Cached alumni for the current request. */
    private ?Collection $cachedAlumni = null;

    /** @var Collection<string, UmpSalary>|null Cached UMP lookup for the current request. */
    private ?Collection $cachedUmp = null;

    public function __construct(private readonly IkuCalculatorService $iku) {}

    /**
     * Load all alumni (with ALL relations eager-loaded) once and cache on
     * this service instance. Every subsequent call reuses the same collection,
     * so summary(), facultyRecap(), monthlyBreakdown() and alumniByProvince()
     * together cost exactly ONE database round-trip instead of 3-4.
     *
     * @param  array{faculty_id?: int, program_study_id?: int, jenjang?: list<string>}  $filters
     */
    public function loadAlumni(User $user, array $filters = []): Collection
    {
        if ($this->cachedAlumni === null) {
            $this->cachedAlumni = $this->scopedAlumni($user, $filters)
                ->with(['tracerResponse.workProvince', 'faculty', 'studyProgram'])
                ->get();
        }

        return $this->cachedAlumni;
    }

    /**
     * Role-scoped, per-graduation-year aggregation used by the main dashboard
     * charts (see Desain Sistem Tracer Studi.pdf, "Dashboard utama").
     *
     * @param  array{faculty_id?: int, program_study_id?: int, jenjang?: list<string>}  $filters
     */
    public function summary(User $user, array $filters = []): array
    {
        $alumni = $this->loadAlumni($user, $filters);
        $years = $this->yearRange($alumni);
        $umpByProvinceYear = $this->umpLookup();

        $perYear = [];
        foreach ($years as $year) {
            $perYear[$year] = $this->aggregateFor($alumni->where('graduation_year', $year), $umpByProvinceYear, $year);
        }

        return ['years' => $years, 'data' => $perYear];
    }

    /**
     * Per-faculty recap for a single graduation year (rekap tracer berdasarkan
     * fakultas on the dashboard), plus a university/scope-wide total row.
     *
     * @param  array{faculty_id?: int, program_study_id?: int, jenjang?: list<string>}  $filters
     */
    public function facultyRecap(User $user, int $year, array $filters = []): array
    {
        $alumni = $this->loadAlumni($user, $filters)->where('graduation_year', $year);

        $umpLookup = $this->umpLookup();

        $rows = $alumni->groupBy('faculty_id')
            ->map(function (Collection $facultyAlumni) use ($umpLookup, $year) {
                return [
                    'faculty' => $facultyAlumni->first()->faculty?->name ?? '-',
                    ...$this->aggregateFor($facultyAlumni, $umpLookup, $year),
                ];
            })
            ->sortBy('faculty')
            ->values();

        return [
            'year' => $year,
            'rows' => $rows,
            'total' => $this->aggregateFor($alumni, $umpLookup, $year),
        ];
    }

    /**
     * @param  Collection<int, UmpSalary>  $umpLookup  keyed by "province_id-year"
     */
    private function aggregateFor(Collection $cohort, Collection $umpLookup, int $year): array
    {
        $responded = $cohort->filter(fn (Alumni $a) => $a->tracerResponse !== null);
        $responses = $responded->map(fn (Alumni $a) => $a->tracerResponse);

        $bekerja = $responses->filter(fn (TracerResponse $r) => (int) $r->f8 === TracerResponse::STATUS_BEKERJA);
        $wiraswasta = $responses->filter(fn (TracerResponse $r) => (int) $r->f8 === TracerResponse::STATUS_WIRASWASTA);
        $melanjutkan = $responses->filter(fn (TracerResponse $r) => (int) $r->f8 === TracerResponse::STATUS_MELANJUTKAN_STUDI);

        // IKU is scoped to D3/S1 alumni only, independent of any jenjang filter applied above.
        $ikuCohort = $cohort->filter(fn (Alumni $a) => in_array($a->studyProgram?->level, self::IKU_ELIGIBLE_LEVELS, true));
        $ikuResponses = $ikuCohort->filter(fn (Alumni $a) => $a->tracerResponse !== null)->map(fn (Alumni $a) => $a->tracerResponse);

        $bobotTotal = $ikuResponses->sum(function (TracerResponse $r) use ($umpLookup, $year) {
            $ump = $umpLookup->get($r->work_province_id.'-'.$year);

            return $this->iku->bobot($r, $ump);
        });

        $gajiTerisi = $this->filledSalaries($responses);
        $waktuTungguTerisi = $bekerja->pluck('f502')->filter(fn ($v) => $v !== null);

        return [
            'jumlah_alumni' => $cohort->count(),
            'responden' => $responded->count(),
            'bekerja' => $bekerja->count(),
            'wiraswasta' => $wiraswasta->count(),
            'melanjutkan_studi' => $melanjutkan->count(),
            'bobot_total' => round($bobotTotal, 2),
            'persentase_responden' => $this->percent($responded->count(), $cohort->count()),
            'iku_berdasar_responden' => $this->percent($bobotTotal, $ikuResponses->count()),
            'iku_berdasar_lulusan' => $this->percent($bobotTotal, $ikuCohort->count()),
            'rata_rata_penghasilan' => $gajiTerisi->isEmpty() ? 0 : round($gajiTerisi->avg(), 2),
            'rata_rata_waktu_tunggu' => $waktuTungguTerisi->isEmpty() ? 0 : round($waktuTungguTerisi->avg(), 2),
            'posisi_wiraswasta' => [
                'founder' => $wiraswasta->where('f5c', 1)->count(),
                'co_founder' => $wiraswasta->where('f5c', 2)->count(),
                'staff' => $wiraswasta->where('f5c', 3)->count(),
                'freelance' => $wiraswasta->where('f5c', 4)->count(),
            ],
            'tempat_bekerja' => [
                'instansi_pemerintah' => $bekerja->where('f1101', 1)->count(),
                'organisasi_non_profit' => $bekerja->where('f1101', 2)->count(),
                'perusahaan_swasta' => $bekerja->where('f1101', 3)->count(),
                'wiraswasta_sendiri' => $bekerja->where('f1101', 4)->count(),
                'lainnya' => $bekerja->where('f1101', 5)->count(),
                'bumn_bumd' => $bekerja->where('f1101', 6)->count(),
                'multilateral' => $bekerja->where('f1101', 7)->count(),
            ],
        ];
    }

    /**
     * Alumni counted by the province they work in (f5a1/work_province_id on
     * their tracer response). Across all graduation years in scope by
     * default (a cohort-year slice isn't useful for the dashboard's
     * distribution map, unlike its other per-year breakdowns) — pass $year
     * to scope it to a single cohort instead (used by the "Laporan Tracer
     * Study" PDF's "Sebaran Tempat Kerja" section). Alumni who haven't
     * answered the tracer form, or left the work-location question blank,
     * aren't counted anywhere here (there's no province to plot them at).
     *
     * @param  array{faculty_id?: int, program_study_id?: int, jenjang?: list<string>}  $filters
     * @return list<array{code: string, name: string, jumlah: int, lat: ?float, lng: ?float, is_luar_negeri: bool}>
     */
    public function alumniByProvince(User $user, array $filters = [], ?int $year = null): array
    {
        $alumni = $this->loadAlumni($user, $filters);

        if ($year !== null) {
            $alumni = $alumni->where('graduation_year', $year);
        }

        return $this->groupByProvince($alumni);
    }

    /**
     * @param  Collection<int, Alumni>  $alumni
     * @return list<array{code: string, name: string, jumlah: int, lat: ?float, lng: ?float, is_luar_negeri: bool}>
     */
    private function groupByProvince(Collection $alumni): array
    {
        return $alumni
            ->map(fn (Alumni $a) => $a->tracerResponse?->workProvince)
            ->filter()
            ->groupBy('id')
            ->map(function (Collection $group) {
                /** @var Province $province */
                $province = $group->first();
                $coords = ProvinceCentroids::forName($province->name);

                return [
                    'code' => $province->code,
                    'name' => $province->name,
                    'jumlah' => $group->count(),
                    'lat' => $coords[0] ?? null,
                    'lng' => $coords[1] ?? null,
                    'is_luar_negeri' => ProvinceCentroids::isLuarNegeri($province->name),
                ];
            })
            ->sortByDesc('jumlah')
            ->values()
            ->all();
    }

    /**
     * Bucketed breakdowns for the "Laporan Tracer Study" PDF
     * (AutoReportController) that summary()/facultyRecap() don't already
     * provide: status lulusan, masa tunggu buckets, pendapatan buckets, and
     * province-of-work ranking — all scoped to a single graduation year.
     *
     * @param  array{faculty_id?: int, program_study_id?: int, jenjang?: list<string>}  $filters
     */
    public function reportBreakdown(User $user, int $year, array $filters = []): array
    {
        $alumni = $this->loadAlumni($user, $filters)->where('graduation_year', $year);
        $responded = $alumni->filter(fn (Alumni $a) => $a->tracerResponse !== null);
        $responses = $responded->map(fn (Alumni $a) => $a->tracerResponse);

        $bekerja = $responses->filter(fn (TracerResponse $r) => (int) $r->f8 === TracerResponse::STATUS_BEKERJA)->count();
        $wiraswasta = $responses->filter(fn (TracerResponse $r) => (int) $r->f8 === TracerResponse::STATUS_WIRASWASTA)->count();
        $studiLanjut = $responses->filter(fn (TracerResponse $r) => (int) $r->f8 === TracerResponse::STATUS_MELANJUTKAN_STUDI)->count();

        // Imported rows store 0 instead of null in f502 for every status; 0
        // months is also a real answer, so scope to bekerja/wiraswasta.
        $working = $responses->filter(fn (TracerResponse $r) => in_array((int) $r->f8, [
            TracerResponse::STATUS_BEKERJA, TracerResponse::STATUS_WIRASWASTA,
        ], true));
        $waktuTunggu = $working->pluck('f502')->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v);
        $gaji = $this->filledSalaries($responses);

        return [
            'status_lulusan' => [
                'bekerja' => $bekerja,
                'wiraswasta' => $wiraswasta,
                'studi_lanjut' => $studiLanjut,
                'belum_bekerja' => max(0, $responded->count() - $bekerja - $wiraswasta - $studiLanjut),
            ],
            'masa_tunggu' => [
                '<=6 bulan' => $waktuTunggu->filter(fn ($v) => $v <= 6)->count(),
                '7-12 bulan' => $waktuTunggu->filter(fn ($v) => $v > 6 && $v <= 12)->count(),
                '>12 bulan' => $waktuTunggu->filter(fn ($v) => $v > 12)->count(),
            ],
            'pendapatan' => [
                '<3 juta' => $gaji->filter(fn ($v) => $v < 3_000_000)->count(),
                '3-5 juta' => $gaji->filter(fn ($v) => $v >= 3_000_000 && $v < 5_000_000)->count(),
                '5-10 juta' => $gaji->filter(fn ($v) => $v >= 5_000_000 && $v < 10_000_000)->count(),
                '>10 juta' => $gaji->filter(fn ($v) => $v >= 10_000_000)->count(),
            ],
            'rata_rata_pendapatan' => $gaji->isEmpty() ? 0 : round($gaji->avg(), 2),
            'tempat_kerja' => $this->groupByProvince($alumni),
            'f14' => $this->countBy($responses, 'f14', [
                1 => 'Sangat relevan', 2 => 'Relevan', 3 => 'Cukup relevan', 4 => 'Kurang relevan', 5 => 'Tidak relevan',
            ]),
            'f15' => $this->countBy($responses, 'f15', [
                1 => 'Setingkat lebih tinggi', 2 => 'Tingkat yang sama', 3 => 'Setingkat lebih rendah', 4 => 'Tidak perlu pendidikan tinggi',
            ]),
            'jenis_instansi' => $this->countBy($responses, 'f1101', [
                3 => 'Perusahaan swasta', 1 => 'Instansi/lembaga pemerintah', 5 => 'Lainnya',
                6 => 'BUMN/BUMD', 7 => 'Institusi/organisasi multilateral', 2 => 'Institusi/organisasi nirlaba',
            ], sortByCount: true),
            'ruang_lingkup' => $this->countBy($responses, 'f5d', [
                1 => 'Lokal/Wilayah', 2 => 'Nasional', 3 => 'Multinasional/Internasional',
            ]),
            'cara_mencari_kerja' => $this->caraMencariKerja($responses),
            'metode_pembelajaran' => $this->metodePembelajaran($responses),
            'kompetensi' => $this->kompetensi($responses),
            'iku' => $this->ikuBreakdown($alumni, $year),
        ];
    }

    /**
     * Salaries (f505) respondents actually entered. Imported rows store 0 for
     * an unanswered salary, so 0 counts as empty rather than as a real income
     * — otherwise it drags every average down.
     *
     * @param  Collection<int, TracerResponse>  $responses
     * @return Collection<int, float>
     */
    private function filledSalaries(Collection $responses): Collection
    {
        return $responses->pluck('f505')
            ->filter(fn ($v) => $v !== null && (float) $v > 0)
            ->map(fn ($v) => (float) $v)
            ->values();
    }

    /**
     * @param  Collection<int, TracerResponse>  $responses
     * @param  array<int, string>  $labels
     * @return array<string, int>
     */
    private function countBy(Collection $responses, string $field, array $labels, bool $sortByCount = false): array
    {
        $counts = [];
        foreach ($labels as $code => $label) {
            $counts[$label] = $responses->filter(fn (TracerResponse $r) => (int) $r->{$field} === $code && $r->{$field} !== null)->count();
        }

        if ($sortByCount) {
            arsort($counts);
        }

        return $counts;
    }

    /**
     * F401-F416 (minus the unused f410/f412 columns): a checkbox question,
     * so counts don't sum to the respondent total — basis is everyone whose
     * status makes this question applicable (bekerja/mencari kerja).
     *
     * @param  Collection<int, TracerResponse>  $responses
     * @return array{basis: int, items: array<string, array{jumlah: int, persen: float}>}
     */
    private function caraMencariKerja(Collection $responses): array
    {
        $applicable = $responses->filter(fn (TracerResponse $r) => in_array((int) $r->f8, [
            TracerResponse::STATUS_BEKERJA, TracerResponse::STATUS_MENCARI_KERJA,
        ], true));
        $basis = $applicable->count();

        $labels = [
            401 => 'Iklan koran/majalah/brosur', 402 => 'Melamar langsung tanpa tahu lowongan',
            403 => 'Bursa/pameran kerja', 404 => 'Internet/medsos/job portal', 405 => 'Dihubungi perusahaan',
            406 => 'Menghubungi Kementerian Ketenagakerjaan', 407 => 'Agen tenaga kerja swasta',
            408 => 'Info karir dari fakultas/universitas', 409 => 'Kantor kemahasiswaan/alumni',
            411 => 'Jejaring/relasi (dosen, orang tua, teman)', 413 => 'Penempatan kerja/magang',
            414 => 'Bekerja di tempat sama saat kuliah', 415 => 'Lainnya',
        ];

        $items = [];
        foreach ($labels as $code => $label) {
            $jumlah = $applicable->filter(fn (TracerResponse $r) => (bool) $r->{"f{$code}"})->count();
            $items[$label] = ['jumlah' => $jumlah, 'persen' => $this->percent($jumlah, $basis)];
        }
        uasort($items, fn ($a, $b) => $b['jumlah'] <=> $a['jumlah']);

        return ['basis' => $basis, 'items' => $items];
    }

    /**
     * F1761-F1782: each competency rated twice on 1 (Sangat Rendah) to 5
     * (Sangat Tinggi) — A (odd code) how well the graduate mastered it at
     * graduation, B (even code) how much their job needs it. As with the
     * learning methods, only 1-5 counts and unanswered competencies are left out.
     *
     * @param  Collection<int, TracerResponse>  $responses
     * @return list<array{label: string, saat_lulus: float, dibutuhkan: float, selisih: float, n: int}>
     */
    private function kompetensi(Collection $responses): array
    {
        $labels = [
            'Etika' => [1761, 1762], 'Keahlian berdasarkan bidang ilmu' => [1763, 1764], 'Bahasa Inggris' => [1765, 1766],
            'Penggunaan teknologi informasi' => [1767, 1768], 'Komunikasi' => [1769, 1770], 'Kerja sama tim' => [1771, 1772],
            'Pengembangan diri' => [1773, 1774], 'Berpikir kritis' => [1775, 1776], 'Kreativitas' => [1777, 1778],
            'Kewirausahaan' => [1779, 1780], 'Adaptasi' => [1781, 1782],
        ];

        $answered = fn (string $field) => $responses->pluck($field)
            ->filter(fn ($v) => $v !== null && (int) $v >= 1 && (int) $v <= 5)
            ->map(fn ($v) => (int) $v);

        $items = [];
        foreach ($labels as $label => [$a, $b]) {
            $saatLulus = $answered("f{$a}");
            $dibutuhkan = $answered("f{$b}");

            if ($saatLulus->isEmpty() || $dibutuhkan->isEmpty()) {
                continue;
            }

            $items[] = [
                'label' => $label,
                'saat_lulus' => round($saatLulus->avg(), 2),
                'dibutuhkan' => round($dibutuhkan->avg(), 2),
                'selisih' => round($dibutuhkan->avg() - $saatLulus->avg(), 2),
                'n' => $saatLulus->count(),
            ];
        }

        return $items;
    }

    /**
     * Where the IKU figure comes from, for D3/S1 alumni of one graduation
     * year: what each status contributes, and what holds the score down —
     * alumni who never answered (counted as 0), work found after 12 months,
     * pay at or below 1.2 x UMP, and work provinces with no UMP on record
     * (which the calculator can only treat as below it).
     *
     * @param  Collection<int, Alumni>  $alumni
     * @return array<string, mixed>
     */
    private function ikuBreakdown(Collection $alumni, int $year): array
    {
        $umpLookup = $this->umpLookup();
        $eligible = $alumni->filter(fn (Alumni $a) => in_array($a->studyProgram?->level, self::IKU_ELIGIBLE_LEVELS, true));

        $komponen = array_fill_keys(['bekerja', 'wiraswasta', 'studi_lanjut', 'lainnya'], ['jumlah' => 0, 'bobot' => 0.0]);
        $tidakMengisi = 0;
        $bekerjaWiraswasta = 0;
        $gajiDiAtasUmp = 0;
        $tanpaDataUmp = 0;
        $masaTungguTidakDihitung = 0;

        foreach ($eligible as $a) {
            $r = $a->tracerResponse;
            if (! $r) {
                $tidakMengisi++;

                continue;
            }

            $f8 = (int) $r->f8;
            $key = match ($f8) {
                TracerResponse::STATUS_BEKERJA => 'bekerja',
                TracerResponse::STATUS_WIRASWASTA => 'wiraswasta',
                TracerResponse::STATUS_MELANJUTKAN_STUDI => 'studi_lanjut',
                default => 'lainnya',
            };
            $ump = $umpLookup->get($r->work_province_id.'-'.$year);

            $komponen[$key]['jumlah']++;
            $komponen[$key]['bobot'] += $this->iku->bobot($r, $ump);

            if (in_array($key, ['bekerja', 'wiraswasta'], true)) {
                $bekerjaWiraswasta++;
                if ($r->f502 === null || (int) $r->f502 >= 12) {
                    $masaTungguTidakDihitung++;
                }
                if ($ump === null) {
                    $tanpaDataUmp++;
                } elseif ($this->iku->gajiMemenuhiUmp($r->f505, $ump)) {
                    $gajiDiAtasUmp++;
                }
            }
        }

        $bobotTotal = array_sum(array_column($komponen, 'bobot'));

        return [
            'lulusan' => $eligible->count(),
            'tidak_mengisi' => $tidakMengisi,
            'komponen' => array_map(fn ($k) => ['jumlah' => $k['jumlah'], 'bobot' => round($k['bobot'], 2)], $komponen),
            'bobot_total' => round($bobotTotal, 2),
            'nilai' => $this->percent($bobotTotal, $eligible->count()),
            'bekerja_wiraswasta' => $bekerjaWiraswasta,
            'gaji_di_atas_ump' => $gajiDiAtasUmp,
            'tanpa_data_ump' => $tanpaDataUmp,
            'masa_tunggu_tidak_dihitung' => $masaTungguTidakDihitung,
        ];
    }

    /**
     * F21-F37: "seberapa besar penekanan" rating, 1 (Sangat Besar) to 5
     * (Tidak Ada) — lower is more emphasis. Imported rows store 0 for an
     * unanswered item, so only 1-5 counts as an answer; an item nobody has
     * answered yet (e.g. those added in the October 2026 form rework) is
     * left out entirely rather than shown with a meaningless average.
     *
     * @param  Collection<int, TracerResponse>  $responses
     * @return list<array{label: string, rata_rata: float, n: int}>
     */
    private function metodePembelajaran(Collection $responses): array
    {
        $labels = [
            21 => 'Perkuliahan', 22 => 'Mendemonstrasikan', 23 => 'Penelitian/partisipasi riset',
            24 => 'Magang/praktik kerja', 25 => 'Praktikum', 26 => 'Kerja lapangan', 27 => 'Diskusi',
            28 => 'Responsi/tutorial', 29 => 'Seminar', 30 => 'Studio', 31 => 'Perancangan',
            32 => 'Pengembangan (produk/karya seni)', 33 => 'Tugas akhir', 34 => 'Pelatihan bela negara',
            35 => 'Pertukaran pelajar', 36 => 'Wirausaha', 37 => 'Pengabdian kepada masyarakat',
        ];

        $items = [];
        foreach ($labels as $code => $label) {
            $values = $responses->pluck("f{$code}")
                ->filter(fn ($v) => $v !== null && (int) $v >= 1 && (int) $v <= 5)
                ->map(fn ($v) => (int) $v);

            if ($values->isEmpty()) {
                continue;
            }

            $items[] = [
                'label' => $label,
                'rata_rata' => round($values->avg(), 2),
                'n' => $values->count(),
            ];
        }

        return $items;
    }

    /**
     * Month-by-month comparison table between two graduation years (mirrors the
     * "Terdapat table..." example on page 5 of Desain Sistem Tracer Studi.pdf),
     * keyed by the month the response was submitted (waktu_update).
     */
    public function monthlyBreakdown(User $user, int $yearA, int $yearB, array $filters = []): array
    {
        $alumni = $this->loadAlumni($user, $filters)->whereIn('graduation_year', [$yearA, $yearB]);

        $months = range(1, 12);
        $table = [];

        foreach ($months as $month) {
            $row = ['month' => $month];

            foreach ([$yearA, $yearB] as $year) {
                $responses = $alumni->where('graduation_year', $year)
                    ->map(fn (Alumni $a) => $a->tracerResponse)
                    ->filter(fn (?TracerResponse $r) => $r !== null && $r->submitted_at !== null
                        && (int) $r->submitted_at->timezone(config('app.display_timezone'))->format('n') === $month);

                $bekerja = $responses->filter(fn (TracerResponse $r) => (int) $r->f8 === TracerResponse::STATUS_BEKERJA);
                $waktuTunggu = $bekerja->pluck('f502')->filter(fn ($v) => $v !== null);

                $row[$year] = [
                    'jumlah_lulusan_bekerja' => $bekerja->count(),
                    'rata_rata_waktu_tunggu' => $waktuTunggu->isEmpty() ? 0 : round($waktuTunggu->avg(), 2),
                    'jumlah_lanjut_studi' => $responses->filter(fn (TracerResponse $r) => (int) $r->f8 === TracerResponse::STATUS_MELANJUTKAN_STUDI)->count(),
                    'jumlah_wirausaha' => $responses->filter(fn (TracerResponse $r) => (int) $r->f8 === TracerResponse::STATUS_WIRASWASTA)->count(),
                ];
            }

            $table[] = $row;
        }

        return $table;
    }

    private function scopedAlumni(User $user, array $filters)
    {
        $query = Alumni::query()->visibleTo($user);

        if (! empty($filters['faculty_id'])) {
            $query->where('faculty_id', $filters['faculty_id']);
        }

        if (! empty($filters['program_study_id'])) {
            $query->where('program_study_id', $filters['program_study_id']);
        }

        if (! empty($filters['jenjang'])) {
            $query->whereHas('studyProgram', fn ($q) => $q->whereIn('level', (array) $filters['jenjang']));
        }

        return $query;
    }

    private function yearRange(Collection $alumni): array
    {
        if ($alumni->isEmpty()) {
            return [(int) now()->format('Y')];
        }

        $min = (int) $alumni->min('graduation_year');
        $max = (int) $alumni->max('graduation_year');

        return range($min, $max);
    }

    /**
     * Load UMP once per instance and cache — avoids a second UmpSalary::all()
     * call when summary() and facultyRecap() both need it.
     *
     * @return Collection<string, UmpSalary> keyed by "province_id-year"
     */
    private function umpLookup(): Collection
    {
        if ($this->cachedUmp === null) {
            $this->cachedUmp = UmpSalary::all()->keyBy(fn (UmpSalary $u) => $u->province_id.'-'.$u->year);
        }

        return $this->cachedUmp;
    }

    private function percent(float $numerator, int $denominator): float
    {
        return $denominator > 0 ? round(($numerator / $denominator) * 100, 1) : 0.0;
    }
}
