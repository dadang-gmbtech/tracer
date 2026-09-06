<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\EmployerResponse;
use App\Models\Faculty;
use App\Models\User;
use App\Services\EmployerDashboardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_empty_state_renders_when_there_is_no_employer_feedback_yet(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('employer.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada data pengguna alumni');
    }

    public function test_super_admin_sees_the_employer_dashboard(): void
    {
        $alumni = Alumni::factory()->create(['graduation_year' => 2024]);
        EmployerResponse::factory()->create(['alumni_id' => $alumni->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('employer.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Pengguna Alumni')
            ->assertSee('Indeks Kepuasan Keseluruhan');
    }

    public function test_index_converts_the_1_to_4_rating_into_a_0_to_100_index_where_lower_ratings_score_higher(): void
    {
        $alumni = Alumni::factory()->create(['graduation_year' => 2024]);
        // 1 = Sangat Baik on every question -> the best possible index (100%).
        EmployerResponse::factory()->create(['alumni_id' => $alumni->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $summary = app(EmployerDashboardService::class)->summary($admin);

        $this->assertSame(1, $summary['total']['jumlah_respon']);
        $this->assertSame(100.0, $summary['total']['indeks_keseluruhan']);
    }

    public function test_a_response_with_the_worst_rating_scores_a_zero_index(): void
    {
        $alumni = Alumni::factory()->create(['graduation_year' => 2024]);
        EmployerResponse::factory()->create([
            'alumni_id' => $alumni->id,
            'q1_kerja_sama_tim' => 4, 'q2_pengembangan_diri' => 4, 'q3_komunikasi' => 4,
            'q4_teknologi_informasi' => 4, 'q5_bahasa_asing' => 4, 'q6_keahlian' => 4, 'q7_integritas' => 4,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $summary = app(EmployerDashboardService::class)->summary($admin);

        $this->assertSame(0.0, $summary['total']['indeks_keseluruhan']);
    }

    public function test_recap_table_renders_when_more_than_one_faculty_has_feedback(): void
    {
        $facultyA = Faculty::factory()->create(['name' => 'Fakultas A']);
        $facultyB = Faculty::factory()->create(['name' => 'Fakultas B']);
        $alumniA = Alumni::factory()->create(['faculty_id' => $facultyA->id, 'graduation_year' => 2024]);
        $alumniB = Alumni::factory()->create(['faculty_id' => $facultyB->id, 'graduation_year' => 2024]);
        EmployerResponse::factory()->create(['alumni_id' => $alumniA->id]);
        EmployerResponse::factory()->create(['alumni_id' => $alumniB->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('employer.dashboard'))
            ->assertOk()
            ->assertSee('Rekap Pengguna Alumni Berdasarkan Fakultas')
            ->assertSee('Fakultas A')
            ->assertSee('Fakultas B');
    }

    public function test_faculty_recap_is_scoped_to_the_selected_year(): void
    {
        $faculty = Faculty::factory()->create();
        $alumni2023 = Alumni::factory()->create(['faculty_id' => $faculty->id, 'graduation_year' => 2023]);
        $alumni2024 = Alumni::factory()->create(['faculty_id' => $faculty->id, 'graduation_year' => 2024]);
        EmployerResponse::factory()->create(['alumni_id' => $alumni2023->id]);
        EmployerResponse::factory()->create(['alumni_id' => $alumni2024->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $recap2023 = app(EmployerDashboardService::class)->facultyRecap($admin, 2023);
        $recap2024 = app(EmployerDashboardService::class)->facultyRecap($admin, 2024);

        $this->assertSame(1, $recap2023['total']['jumlah_respon']);
        $this->assertSame(1, $recap2024['total']['jumlah_respon']);

        $this->actingAs($admin)
            ->get(route('employer.dashboard', ['recap_year' => 2023]))
            ->assertOk();
    }

    public function test_faculty_recap_only_lists_faculties_within_scope(): void
    {
        $ownFaculty = Faculty::factory()->create();
        $otherFaculty = Faculty::factory()->create();

        $ownAlumni = Alumni::factory()->create(['faculty_id' => $ownFaculty->id, 'graduation_year' => 2024]);
        $otherAlumni = Alumni::factory()->create(['faculty_id' => $otherFaculty->id, 'graduation_year' => 2024]);
        EmployerResponse::factory()->create(['alumni_id' => $ownAlumni->id]);
        EmployerResponse::factory()->create(['alumni_id' => $otherAlumni->id]);

        $admin = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $admin->assignRole('Admin Fakultas');

        $recap = app(EmployerDashboardService::class)->facultyRecap($admin, 2024);

        $this->assertSame(1, $recap['total']['jumlah_respon']);
    }

    public function test_faculty_comparison_returns_one_series_per_selected_faculty(): void
    {
        $facultyA = Faculty::factory()->create(['name' => 'Fakultas A']);
        $facultyB = Faculty::factory()->create(['name' => 'Fakultas B']);
        $alumniA = Alumni::factory()->create(['faculty_id' => $facultyA->id, 'graduation_year' => 2024]);
        $alumniB = Alumni::factory()->create(['faculty_id' => $facultyB->id, 'graduation_year' => 2024]);
        // Sangat Baik on every question -> 100% index.
        EmployerResponse::factory()->create(['alumni_id' => $alumniA->id]);
        // Kurang on every question -> 0% index.
        EmployerResponse::factory()->create([
            'alumni_id' => $alumniB->id,
            'q1_kerja_sama_tim' => 4, 'q2_pengembangan_diri' => 4, 'q3_komunikasi' => 4,
            'q4_teknologi_informasi' => 4, 'q5_bahasa_asing' => 4, 'q6_keahlian' => 4, 'q7_integritas' => 4,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $comparison = app(EmployerDashboardService::class)->indeksPerFacultyPerYear($admin, [$facultyA->id, $facultyB->id]);

        $this->assertSame(100.0, $comparison['per_pertanyaan']['q5_bahasa_asing']['series']['Fakultas A'][2024]);
        $this->assertSame(0.0, $comparison['per_pertanyaan']['q5_bahasa_asing']['series']['Fakultas B'][2024]);
        $this->assertSame(100.0, $comparison['per_pertanyaan']['q1_kerja_sama_tim']['series']['Fakultas A'][2024]);
        $this->assertSame(0.0, $comparison['per_pertanyaan']['q1_kerja_sama_tim']['series']['Fakultas B'][2024]);
    }

    public function test_checking_faculties_switches_the_dashboard_to_the_comparison_chart(): void
    {
        $facultyA = Faculty::factory()->create(['name' => 'Fakultas A']);
        $alumniA = Alumni::factory()->create(['faculty_id' => $facultyA->id, 'graduation_year' => 2024]);
        EmployerResponse::factory()->create(['alumni_id' => $alumniA->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('employer.dashboard', ['compare_faculty_ids' => [$facultyA->id]]))
            ->assertOk()
            ->assertSee('Perbandingan Fakultas — Bahasa Asing')
            ->assertSee('Perbandingan Fakultas — Kerja Sama Tim')
            ->assertDontSee('Indeks per Pertanyaan per Tahun');
    }

    public function test_alumni_is_redirected_away_from_the_employer_dashboard(): void
    {
        $alumni = Alumni::factory()->create();
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)
            ->get(route('employer.dashboard'))
            ->assertRedirect(route('tracer.edit', $alumni));
    }
}
