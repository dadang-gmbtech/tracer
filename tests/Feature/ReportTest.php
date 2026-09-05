<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_universitas_can_view_reports_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
    }

    public function test_alumni_is_redirected_away_from_reports(): void
    {
        $alumni = Alumni::factory()->create();
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertRedirect(route('tracer.edit', $alumni));
    }

    public function test_alumni_cannot_upload_a_report(): void
    {
        $alumni = Alumni::factory()->create();
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)->get(route('reports.create'))->assertForbidden();
    }
}
