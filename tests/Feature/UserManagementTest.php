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

    public function test_creating_a_user_with_a_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'sudah.ada@unsoed.ac.id']);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Percobaan',
            'email' => 'sudah.ada@unsoed.ac.id',
            'password' => 'password123',
            'role' => 'Surveyor',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_universitas_can_update_a_user_keeping_their_own_email(): void
    {
        // Regression: the unique rule used to be built as a raw string
        // ('unique:users,email,'.($user?->id)) — on create that left a
        // trailing empty "ignore id" segment, which Postgres rejects when
        // comparing it to the bigint id column (SQLSTATE 22P02); MySQL/
        // SQLite silently tolerate it, which is why this only broke in
        // production. Rule::unique()->ignore($user) must not regress this:
        // saving a user's own unchanged email must still succeed.
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');
        $target = User::factory()->create(['email' => 'tetap.sama@unsoed.ac.id']);
        $target->assignRole('Surveyor');

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => 'Nama Baru',
            'email' => 'tetap.sama@unsoed.ac.id',
            'role' => 'Surveyor',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Nama Baru', 'email' => 'tetap.sama@unsoed.ac.id']);
    }

    public function test_updating_a_user_to_another_users_email_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');
        User::factory()->create(['email' => 'sudah.dipakai@unsoed.ac.id']);
        $target = User::factory()->create();
        $target->assignRole('Surveyor');

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => 'sudah.dipakai@unsoed.ac.id',
            'role' => 'Surveyor',
        ]);

        $response->assertSessionHasErrors('email');
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
