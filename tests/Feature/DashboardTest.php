<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\Province;
use App\Models\StudyProgram;
use App\Models\TracerResponse;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_sees_dashboard_with_aggregated_data(): void
    {
        $alumni = Alumni::factory()->create(['graduation_year' => 2024]);
        TracerResponse::factory()->create(['alumni_id' => $alumni->id, 'f8' => 1]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tracer Studi');
    }

    public function test_dashboard_shows_faculty_recap_for_the_selected_year(): void
    {
        $facultyA = Faculty::factory()->create(['name' => 'Fakultas A']);
        $facultyB = Faculty::factory()->create(['name' => 'Fakultas B']);

        $alumniA = Alumni::factory()->create(['faculty_id' => $facultyA->id, 'graduation_year' => 2024]);
        Alumni::factory()->create(['faculty_id' => $facultyB->id, 'graduation_year' => 2024]);
        TracerResponse::factory()->create(['alumni_id' => $alumniA->id, 'f8' => 1]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('dashboard', ['recap_year' => 2024]))
            ->assertOk()
            ->assertSee('Rekap Tracer Berdasarkan Fakultas')
            ->assertSee('Fakultas A')
            ->assertSee('Fakultas B');
    }

    public function test_dashboard_defaults_the_faculty_recap_to_the_latest_real_year_not_the_current_calendar_year(): void
    {
        // Regression: a cache layer used to resolve recap_year's default by
        // peeking a separate years-only cache that started out empty,
        // silently defaulting to now()->year and showing an empty recap for
        // any faculty whose actual data isn't from the current calendar year.
        $faculty = Faculty::factory()->create();
        $alumni = Alumni::factory()->create(['faculty_id' => $faculty->id, 'graduation_year' => 2019]);
        TracerResponse::factory()->create(['alumni_id' => $alumni->id, 'f8' => 1]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        // No recap_year (or year_a/year_b) in the request — must be resolved
        // from the real data, not defaulted to the current year (there is no
        // 2026 data here at all).
        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame(2019, $response->viewData('recapYear'));
        $this->assertSame(2019, $response->viewData('yearB'));
        $this->assertSame(1, $response->viewData('facultyRecap')['total']['jumlah_alumni']);
    }

    public function test_loading_the_dashboard_only_queries_the_alumni_table_once(): void
    {
        // summary(), facultyRecap(), monthlyBreakdown() and alumniByProvince()
        // each used to run their own identical scoped alumni query — four
        // round-trips against a table with well over 100k rows in
        // production. DashboardService::loadAlumni() memoizes it to one.
        $faculty = Faculty::factory()->create();
        $alumni = Alumni::factory()->create(['faculty_id' => $faculty->id, 'graduation_year' => 2024]);
        TracerResponse::factory()->create(['alumni_id' => $alumni->id, 'f8' => 1]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('dashboard', ['recap_year' => 2024]))->assertOk();
        $alumniQueries = collect(DB::getQueryLog())->filter(fn (array $q) => str_contains($q['query'], '"alumni"'));
        DB::flushQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $alumniQueries, $alumniQueries->pluck('query')->implode("\n"));
    }

    public function test_pimpinan_fakultas_only_sees_their_faculty_data(): void
    {
        $faculty = Faculty::factory()->create();
        $otherFaculty = Faculty::factory()->create();

        Alumni::factory()->create(['faculty_id' => $faculty->id, 'graduation_year' => 2024]);
        Alumni::factory()->create(['faculty_id' => $otherFaculty->id, 'graduation_year' => 2024]);

        $pimpinan = User::factory()->create(['faculty_id' => $faculty->id]);
        $pimpinan->assignRole('Pimpinan Fakultas');

        $this->actingAs($pimpinan)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_iku_percentages_only_count_d3_and_s1_alumni(): void
    {
        $faculty = Faculty::factory()->create();
        $s1 = StudyProgram::factory()->create(['level' => 'S1', 'faculty_id' => $faculty->id]);
        $s2 = StudyProgram::factory()->create(['level' => 'S2', 'faculty_id' => $faculty->id]);

        $s1Alumni = Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $s1->id, 'graduation_year' => 2024]);
        Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $s2->id, 'graduation_year' => 2024]); // S2, never filled the tracer form

        // masa tunggu < 6 bulan, no UMP on file -> bobot 0.6 (see IkuCalculatorServiceTest).
        TracerResponse::factory()->create(['alumni_id' => $s1Alumni->id, 'f8' => 1, 'f502' => 2]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $row = app(DashboardService::class)->summary($admin)['data'][2024];

        // General counts still reflect both alumni (S2 included)...
        $this->assertSame(2, $row['jumlah_alumni']);
        $this->assertSame(1, $row['bekerja']);

        // ...but IKU is scoped to the single S1 alumni: 0.6 / 1 = 60%, not 0.6 / 2 = 30%.
        $this->assertSame(60.0, $row['iku_berdasar_lulusan']);
        $this->assertSame(60.0, $row['iku_berdasar_responden']);
    }

    public function test_average_salary_only_divides_by_alumni_who_filled_it_in(): void
    {
        $faculty = Faculty::factory()->create();

        $filledA = Alumni::factory()->create(['faculty_id' => $faculty->id, 'graduation_year' => 2024]);
        $filledB = Alumni::factory()->create(['faculty_id' => $faculty->id, 'graduation_year' => 2024]);
        $unfilled = Alumni::factory()->create(['faculty_id' => $faculty->id, 'graduation_year' => 2024]);

        TracerResponse::factory()->create(['alumni_id' => $filledA->id, 'f8' => 1, 'f505' => 4_000_000]);
        TracerResponse::factory()->create(['alumni_id' => $filledB->id, 'f8' => 1, 'f505' => 6_000_000]);
        // Responded, but left the salary question blank — must not count toward the divisor.
        TracerResponse::factory()->create(['alumni_id' => $unfilled->id, 'f8' => 1, 'f505' => null]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $row = app(DashboardService::class)->summary($admin)['data'][2024];

        // (4jt + 6jt) / 2 who filled it in = 5jt — not / 3 responden.
        $this->assertSame(3, $row['responden']);
        $this->assertSame(5_000_000.0, $row['rata_rata_penghasilan']);
    }

    public function test_jenjang_filter_narrows_the_dashboard_to_one_level(): void
    {
        $faculty = Faculty::factory()->create();
        $s1 = StudyProgram::factory()->create(['level' => 'S1', 'faculty_id' => $faculty->id]);
        $d3 = StudyProgram::factory()->create(['level' => 'D3', 'faculty_id' => $faculty->id]);

        Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $s1->id, 'graduation_year' => 2024]);
        Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $d3->id, 'graduation_year' => 2024]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $row = app(DashboardService::class)->summary($admin, ['jenjang' => ['S1']])['data'][2024];

        $this->assertSame(1, $row['jumlah_alumni']);

        // Selecting several levels at once (checklist) includes all of them.
        $bothRow = app(DashboardService::class)->summary($admin, ['jenjang' => ['S1', 'D3']])['data'][2024];
        $this->assertSame(2, $bothRow['jumlah_alumni']);
    }

    public function test_jenjang_checklist_filter_works_through_the_http_request(): void
    {
        $faculty = Faculty::factory()->create();
        $s1 = StudyProgram::factory()->create(['level' => 'S1', 'faculty_id' => $faculty->id]);
        $s2 = StudyProgram::factory()->create(['level' => 'S2', 'faculty_id' => $faculty->id]);

        Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $s1->id, 'graduation_year' => 2024]);
        Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $s2->id, 'graduation_year' => 2024]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('dashboard', ['jenjang' => ['S1']]))
            ->assertOk();
    }

    public function test_alumni_is_redirected_away_from_the_dashboard(): void
    {
        $alumni = Alumni::factory()->create();
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('tracer.edit', $alumni));
    }

    public function test_alumni_by_province_groups_and_counts_alumni_by_work_province(): void
    {
        $jakarta = Province::factory()->create(['code' => '31', 'name' => 'D.K.I. Jakarta']); // has a known centroid
        $alumniA = Alumni::factory()->create();
        $alumniB = Alumni::factory()->create();
        TracerResponse::factory()->create(['alumni_id' => $alumniA->id, 'work_province_id' => $jakarta->id]);
        TracerResponse::factory()->create(['alumni_id' => $alumniB->id, 'work_province_id' => $jakarta->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $points = app(DashboardService::class)->alumniByProvince($admin);

        $this->assertCount(1, $points);
        $this->assertSame('D.K.I. Jakarta', $points[0]['name']);
        $this->assertSame(2, $points[0]['jumlah']);
        $this->assertNotNull($points[0]['lat']);
        $this->assertNotNull($points[0]['lng']);
    }

    public function test_alumni_by_province_excludes_alumni_without_a_tracer_response_or_work_location(): void
    {
        Alumni::factory()->create(); // never filled the tracer form
        $noLocation = Alumni::factory()->create();
        TracerResponse::factory()->create(['alumni_id' => $noLocation->id, 'work_province_id' => null]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $points = app(DashboardService::class)->alumniByProvince($admin);

        $this->assertSame([], $points);
    }

    public function test_the_dashboard_shows_the_province_distribution_map(): void
    {
        $jakarta = Province::factory()->create(['code' => '31', 'name' => 'D.K.I. Jakarta']);
        $alumni = Alumni::factory()->create();
        TracerResponse::factory()->create(['alumni_id' => $alumni->id, 'work_province_id' => $jakarta->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Peta Sebaran Alumni Berdasarkan Provinsi');
    }

    public function test_the_dashboard_shows_a_domestic_and_overseas_distribution_table(): void
    {
        $jakarta = Province::factory()->create(['code' => '31', 'name' => 'D.K.I. Jakarta']);
        $luarNegeri = Province::factory()->create(['code' => '99', 'name' => 'Luar Negeri']);

        $domestic = Alumni::factory()->create();
        $overseas = Alumni::factory()->create();
        TracerResponse::factory()->create(['alumni_id' => $domestic->id, 'work_province_id' => $jakarta->id]);
        TracerResponse::factory()->create(['alumni_id' => $overseas->id, 'work_province_id' => $luarNegeri->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Tabel Sebaran Alumni');
        $response->assertSee('D.K.I. Jakarta');
        $response->assertSee('Luar Negeri');
    }

    public function test_the_iku_breakdown_adds_up_to_the_dashboard_iku_and_competencies_skip_zeros(): void
    {
        $prodi = StudyProgram::factory()->create(['level' => 'S1']);
        $make = function (?array $tracer) use ($prodi) {
            $alumni = Alumni::factory()->create([
                'faculty_id' => $prodi->faculty_id, 'program_study_id' => $prodi->id, 'graduation_year' => 2025,
            ]);
            if ($tracer) {
                TracerResponse::factory()->create(['alumni_id' => $alumni->id, ...$tracer]);
            }
        };
        $make(['f8' => 1, 'f502' => 3, 'f505' => 9_000_000, 'f1761' => 3, 'f1762' => 5]);
        $make(['f8' => 4, 'f1761' => 0, 'f1762' => 0]);
        $make(['f8' => 1, 'f502' => 14, 'f505' => 9_000_000]);
        $make(null);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $service = app(DashboardService::class);

        $iku = $service->reportBreakdown($admin, 2025)['iku'];
        $this->assertSame(4, $iku['lulusan']);
        $this->assertSame(1, $iku['tidak_mengisi']);
        $this->assertSame(2, $iku['komponen']['bekerja']['jumlah']);
        $this->assertSame(1, $iku['masa_tunggu_tidak_dihitung']);
        $this->assertSame(2, $iku['tanpa_data_ump']);
        $this->assertEquals($service->summary($admin)['data'][2025]['iku_berdasar_lulusan'], $iku['nilai']);

        $kompetensi = collect($service->reportBreakdown($admin, 2025)['kompetensi'])->keyBy('label');
        $this->assertEquals(3.0, $kompetensi['Etika']['saat_lulus']);
        $this->assertEquals(2.0, $kompetensi['Etika']['selisih']);
        $this->assertSame(1, $kompetensi['Etika']['n']);
    }

    public function test_monthly_recap_groups_by_the_wib_month(): void
    {
        // 31 Jul 20:00 UTC is already 1 Aug 03:00 WIB.
        $alumni = Alumni::factory()->create(['graduation_year' => 2025]);
        TracerResponse::factory()->create(['alumni_id' => $alumni->id, 'f8' => 1, 'submitted_at' => '2025-07-31 20:00:00']);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $rows = collect(app(DashboardService::class)->monthlyBreakdown($admin, 2025, 2024))->keyBy('month');

        $this->assertSame(0, $rows[7][2025]['jumlah_lulusan_bekerja']);
        $this->assertSame(1, $rows[8][2025]['jumlah_lulusan_bekerja']);
    }

    public function test_dashboard_can_be_filtered_to_jenjang_profesi(): void
    {
        $profesi = StudyProgram::factory()->create(['level' => 'Profesi']);
        $s1 = StudyProgram::factory()->create(['level' => 'S1']);
        foreach ([$profesi, $s1, $s1] as $prodi) {
            Alumni::factory()->create([
                'faculty_id' => $prodi->faculty_id, 'program_study_id' => $prodi->id, 'graduation_year' => 2025,
            ]);
        }

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('dashboard', ['jenjang' => ['Profesi']]))
            ->assertOk()
            ->assertSee('name="jenjang[]" value="Profesi"', false);

        $summary = app(DashboardService::class)->summary($admin, ['jenjang' => ['Profesi']]);
        $this->assertSame(1, $summary['data'][2025]['jumlah_alumni']);
    }

    public function test_report_breakdown_ignores_zero_placeholders_left_by_imported_rows(): void
    {
        // Imported rows store 0 (not null) for unanswered ratings, salary and
        // masa tunggu, regardless of the respondent's status.
        $bekerja = Alumni::factory()->create(['graduation_year' => 2025]);
        TracerResponse::factory()->create([
            'alumni_id' => $bekerja->id, 'f8' => 1, 'f502' => 3, 'f505' => 6_000_000, 'f21' => 2, 'f28' => 0,
        ]);
        $studiLanjut = Alumni::factory()->create(['graduation_year' => 2025]);
        TracerResponse::factory()->create([
            'alumni_id' => $studiLanjut->id, 'f8' => 4, 'f502' => 0, 'f505' => 0, 'f21' => 0, 'f28' => 0,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $breakdown = app(DashboardService::class)->reportBreakdown($admin, 2025);

        $this->assertSame(1, array_sum($breakdown['masa_tunggu']));
        $this->assertSame(1, array_sum($breakdown['pendapatan']));
        $this->assertSame(0, $breakdown['pendapatan']['<3 juta']);
        $this->assertEquals(6_000_000, $breakdown['rata_rata_pendapatan']);

        // The dashboard's average uses the same rule, so both always agree.
        $summary = app(DashboardService::class)->summary($admin);
        $this->assertEquals(6_000_000, $summary['data'][2025]['rata_rata_penghasilan']);

        $metode = collect($breakdown['metode_pembelajaran'])->keyBy('label');
        $this->assertEquals(2.0, $metode['Perkuliahan']['rata_rata']);
        $this->assertSame(1, $metode['Perkuliahan']['n']);
        $this->assertFalse($metode->has('Responsi/tutorial'));
    }
}
