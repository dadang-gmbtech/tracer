<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\TracerResponse;
use App\Models\User;
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

    public function test_alumni_is_redirected_away_from_the_dashboard(): void
    {
        $alumni = Alumni::factory()->create();
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('tracer.edit', $alumni));
    }
}
