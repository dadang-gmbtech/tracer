<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_an_alumni_account_cannot_reach_any_bulk_import_route(): void
    {
        // Alumni only need fill-tracer to fill in their own questionnaire —
        // that permission must not also unlock bulk Excel import, which lets
        // the importer create/update data for other alumni.
        $alumnusRecord = Alumni::factory()->create();
        $user = User::factory()->create(['nim' => $alumnusRecord->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)->get(route('tracer.import.form'))->assertForbidden();
        $this->actingAs($user)->get(route('tracer.import.template'))->assertForbidden();
        $this->actingAs($user)->get(route('alumni.import.form'))->assertForbidden();
        $this->actingAs($user)->get(route('alumni.import.template'))->assertForbidden();
        $this->actingAs($user)->get(route('employer.import.form'))->assertForbidden();
        $this->actingAs($user)->get(route('employer.import.template'))->assertForbidden();
    }

    public function test_an_alumni_account_does_not_see_import_links_in_the_navigation(): void
    {
        $alumnusRecord = Alumni::factory()->create();
        $user = User::factory()->create(['nim' => $alumnusRecord->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)
            ->get(route('tracer.edit', $alumnusRecord))
            ->assertOk()
            ->assertDontSee('Impor Data Tracer (Excel)')
            ->assertDontSee('Impor Data Pengguna Alumni (Excel)')
            ->assertDontSee('Impor Data Alumni')
            ->assertDontSee(route('tracer.import.form'), false)
            ->assertDontSee(route('alumni.import.form'), false)
            ->assertDontSee(route('employer.import.form'), false);
    }

    public function test_only_super_admin_and_admin_universitas_can_import_tracer_or_alumni_data(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $this->actingAs($superAdmin)->get(route('tracer.import.form'))->assertOk();
        $this->actingAs($superAdmin)->get(route('alumni.import.form'))->assertOk();

        $adminUniversitas = User::factory()->create();
        $adminUniversitas->assignRole('Admin Universitas');
        $this->actingAs($adminUniversitas)->get(route('tracer.import.form'))->assertOk();
        $this->actingAs($adminUniversitas)->get(route('alumni.import.form'))->assertOk();

        foreach (['Admin Fakultas', 'Admin Prodi', 'Surveyor'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)->get(route('tracer.import.form'))->assertForbidden();
            $this->actingAs($user)->get(route('tracer.import.template'))->assertForbidden();
            $this->actingAs($user)->get(route('alumni.import.form'))->assertForbidden();
            $this->actingAs($user)->get(route('alumni.import.template'))->assertForbidden();
            // Also blocked at the actual submit endpoint, not just the form.
            $this->actingAs($user)->post(route('tracer.import'))->assertForbidden();
            $this->actingAs($user)->post(route('alumni.import'))->assertForbidden();
        }
    }

    public function test_surveyor_and_admin_fakultas_can_still_import_pengguna_alumni_data(): void
    {
        // Unlike tracer/alumni import above, importing employer feedback
        // stays open to faculty-level staff — it's a different kind of data.
        $surveyor = User::factory()->create();
        $surveyor->assignRole('Surveyor');
        $this->actingAs($surveyor)->get(route('employer.import.form'))->assertOk();

        $adminFakultas = User::factory()->create();
        $adminFakultas->assignRole('Admin Fakultas');
        $this->actingAs($adminFakultas)->get(route('employer.import.form'))->assertOk();
    }
}
