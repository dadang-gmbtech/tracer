<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ImpersonationController;
use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_can_login_as_another_user_and_switch_back(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $surveyor = $this->userWithRole('Surveyor', ['faculty_id' => Faculty::factory()->create()->id]);

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $surveyor))
            ->assertRedirect($surveyor->postLoginUrl());

        $this->assertAuthenticatedAs($surveyor);
        $this->assertSame($admin->id, session(ImpersonationController::SESSION_KEY));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Anda sedang login sebagai')
            ->assertSee('Kembali ke akun Super Admin');

        $this->post(route('impersonate.stop'))->assertRedirect(route('admin.users.index'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session(ImpersonationController::SESSION_KEY));
    }

    public function test_impersonating_an_alumni_lands_on_their_tracer_form(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $alumni = Alumni::factory()->create(['nim' => 'A1A001XYZ']);
        $alumniUser = $this->userWithRole('Alumni', ['nim' => $alumni->nim]);

        $this->actingAs($admin)
            ->get(route('alumni.show', $alumni))
            ->assertSee('Login sebagai alumni ini');

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $alumniUser))
            ->assertRedirect(route('tracer.edit', $alumni));
    }

    public function test_only_super_admin_can_login_as_another_user(): void
    {
        $target = $this->userWithRole('Surveyor', ['faculty_id' => Faculty::factory()->create()->id]);

        foreach (['Admin Universitas', 'Admin Fakultas', 'Pimpinan Universitas'] as $role) {
            $actor = $this->userWithRole($role, ['faculty_id' => $target->faculty_id]);

            $this->actingAs($actor)->post(route('admin.users.impersonate', $target))->assertForbidden();
            $this->assertAuthenticatedAs($actor);
        }
    }

    public function test_super_admin_cannot_login_as_another_super_admin_or_an_inactive_user(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $otherAdmin = $this->userWithRole('Super Admin');
        $inactive = $this->userWithRole('Admin Universitas', ['status' => 'inactive']);

        $this->actingAs($admin)->post(route('admin.users.impersonate', $otherAdmin))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.impersonate', $inactive))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.impersonate', $admin))->assertForbidden();
    }

    public function test_an_impersonated_user_cannot_start_another_impersonation(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $first = $this->userWithRole('Admin Universitas');
        $second = $this->userWithRole('Admin Universitas');

        $this->actingAs($admin)->post(route('admin.users.impersonate', $first));

        $this->post(route('admin.users.impersonate', $second))->assertForbidden();
        $this->assertAuthenticatedAs($first);
    }

    public function test_switching_back_requires_an_impersonation_in_progress(): void
    {
        $user = $this->userWithRole('Admin Universitas');

        $this->actingAs($user)->post(route('impersonate.stop'))->assertForbidden();
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_user_list_shows_the_button_only_to_super_admin(): void
    {
        $target = $this->userWithRole('Admin Universitas');

        $this->actingAs($this->userWithRole('Super Admin'))
            ->get(route('admin.users.index'))
            ->assertSee(route('admin.users.impersonate', $target));

        $this->actingAs($this->userWithRole('Admin Universitas'))
            ->get(route('admin.users.index'))
            ->assertDontSee(route('admin.users.impersonate', $target));
    }
}
