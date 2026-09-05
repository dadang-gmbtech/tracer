<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_universitas_can_export_tracer_data_to_excel(): void
    {
        Alumni::factory()->count(3)->create();

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->get(route('export.tracer'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_surveyor_without_export_permission_is_forbidden(): void
    {
        $surveyor = User::factory()->create();
        $surveyor->assignRole('Surveyor');

        $this->actingAs($surveyor)->get(route('export.tracer'))->assertForbidden();
    }
}
