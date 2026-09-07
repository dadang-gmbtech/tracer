<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\Faculty;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
            'status' => 'active',
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

    public function test_admin_universitas_can_deactivate_a_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');
        $target = User::factory()->create(['status' => 'active']);
        $target->assignRole('Surveyor');

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'Surveyor',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'inactive']);
    }

    public function test_an_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'Admin Universitas',
            'status' => 'inactive',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
    }

    public function test_ensure_user_is_active_middleware_logs_out_and_redirects_an_inactive_user(): void
    {
        // EnsureUserIsActive is what makes deactivating a user take effect
        // immediately, even for a session that was already logged in — the
        // checks at each login entry point only block *new* logins. Tested
        // directly against the middleware rather than by chaining two HTTP
        // calls in one test: Laravel's SessionGuard caches its resolved user
        // for the lifetime of the test process, so a second in-process
        // request can't observe a DB update made mid-test the way a real,
        // separate HTTP request would.
        $user = User::factory()->create(['status' => 'inactive']);
        $this->actingAs($user);

        $request = Request::create('/dashboard');
        $request->setLaravelSession($this->app['session']->driver());

        $response = (new EnsureUserIsActive)->handle($request, fn () => response('should not reach here'));

        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertGuest();
    }

    public function test_ensure_user_is_active_middleware_passes_through_an_active_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $response = (new EnsureUserIsActive)->handle(Request::create('/dashboard'), fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertAuthenticatedAs($user);
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
