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

    public function __construct(private readonly IkuCalculatorService $iku) {}

    /**
     * Role-scoped, per-graduation-year aggregation used by the main dashboard
     * charts (see Desain Sistem Tracer Studi.pdf, "Dashboard utama").
     *
     * @param  array{faculty_id?: int, program_study_id?: int, jenjang?: list<string>}  $filters
     */
    public function summary(User $user, array $filters = []): array
    {
        $alumni = $this->scopedAlumni($user, $filters)->with(['tracerResponse', 'studyProgram'])->get();
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
        $alumni = $this->scopedAlumni($user, $filters)
            ->where('graduation_year', $year)
            ->with(['tracerResponse', 'faculty', 'studyProgram'])
            ->get();

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

        $gajiTerisi = $responses->pluck('f505')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
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
     * their tracer response), across all graduation years in scope — a
     * cohort-year slice isn't useful for a distribution map, unlike the
     * dashboard's other per-year breakdowns. Alumni who haven't answered the
     * tracer form, or left the work-location question blank, aren't counted
     * anywhere here (there's no province to plot them at).
     *
     * @param  array{faculty_id?: int, program_study_id?: int, jenjang?: list<string>}  $filters
     * @return list<array{code: string, name: string, jumlah: int, lat: ?float, lng: ?float, is_luar_negeri: bool}>
     */
    public function alumniByProvince(User $user, array $filters = []): array
    {
        $alumni = $this->scopedAlumni($user, $filters)->with('tracerResponse.workProvince')->get();

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
     * Month-by-month comparison table between two graduation years (mirrors the
     * "Terdapat table..." example on page 5 of Desain Sistem Tracer Studi.pdf),
     * keyed by the month the response was submitted (waktu_update).
     */
    public function monthlyBreakdown(User $user, int $yearA, int $yearB, array $filters = []): array
    {
        $alumni = $this->scopedAlumni($user, $filters)
            ->whereIn('graduation_year', [$yearA, $yearB])
            ->with('tracerResponse')
            ->get();

        $months = range(1, 12);
        $table = [];

        foreach ($months as $month) {
            $row = ['month' => $month];

            foreach ([$yearA, $yearB] as $year) {
                $responses = $alumni->where('graduation_year', $year)
                    ->map(fn (Alumni $a) => $a->tracerResponse)
                    ->filter(fn (?TracerResponse $r) => $r !== null && $r->submitted_at !== null && (int) $r->submitted_at->format('n') === $month);

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
     * @return Collection<string, UmpSalary> keyed by "province_id-year"
     */
    private function umpLookup(): Collection
    {
        return UmpSalary::all()->keyBy(fn (UmpSalary $u) => $u->province_id.'-'.$u->year);
    }

    private function percent(float $numerator, int $denominator): float
    {
        return $denominator > 0 ? round(($numerator / $denominator) * 100, 1) : 0.0;
    }
}
