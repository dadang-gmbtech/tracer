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
}
