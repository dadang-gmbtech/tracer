<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_universitas_can_create_an_admin_fakultas(): void
    {
        $faculty = Faculty::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Kepala Admin Fakultas',
            'email' => 'admin.fakultas.test@unsoed.ac.id',
            'password' => 'password123',
            'role' => 'Admin Fakultas',
            'faculty_id' => $faculty->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'admin.fakultas.test@unsoed.ac.id', 'faculty_id' => $faculty->id]);
    }

    public function test_admin_fakultas_cannot_create_an_admin_universitas(): void
    {
        $faculty = Faculty::factory()->create();
        $admin = User::factory()->create(['faculty_id' => $faculty->id]);
        $admin->assignRole('Admin Fakultas');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Percobaan',
            'email' => 'percobaan@unsoed.ac.id',
            'password' => 'password123',
            'role' => 'Admin Universitas',
        ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_admin_fakultas_cannot_delete_a_user_from_another_faculty(): void
    {
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();

        $admin = User::factory()->create(['faculty_id' => $facultyA->id]);
        $admin->assignRole('Admin Fakultas');

        $target = User::factory()->create(['faculty_id' => $facultyB->id]);
        $target->assignRole('Admin Prodi');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
