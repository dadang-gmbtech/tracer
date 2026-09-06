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
        $alumniA = Alumni::factory()->create(['faculty_id' => $facultyA->id]);
        $alumniB = Alumni::factory()->create(['faculty_id' => $facultyB->id]);
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

    public function test_faculty_recap_only_lists_faculties_within_scope(): void
    {
        $ownFaculty = Faculty::factory()->create();
        $otherFaculty = Faculty::factory()->create();

        $ownAlumni = Alumni::factory()->create(['faculty_id' => $ownFaculty->id]);
        $otherAlumni = Alumni::factory()->create(['faculty_id' => $otherFaculty->id]);
        EmployerResponse::factory()->create(['alumni_id' => $ownAlumni->id]);
        EmployerResponse::factory()->create(['alumni_id' => $otherAlumni->id]);

        $admin = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $admin->assignRole('Admin Fakultas');

        $recap = app(EmployerDashboardService::class)->facultyRecap($admin);

        $this->assertSame(1, $recap['total']['jumlah_respon']);
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
