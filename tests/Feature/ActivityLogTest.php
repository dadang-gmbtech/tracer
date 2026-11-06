<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ActivityLogTest extends TestCase
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

    public function test_an_admin_login_and_logout_are_logged_in_the_admin_log(): void
    {
        $admin = $this->userWithRole('Admin Universitas', ['email' => 'admin@example.com']);

        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password']);
        $this->post(route('logout'));

        $this->assertDatabaseHas('activity_logs', [
            'category' => 'admin', 'user_id' => $admin->id, 'action' => 'login', 'succeeded' => true,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'category' => 'admin', 'user_id' => $admin->id, 'action' => 'logout',
        ]);
    }

    public function test_a_failed_login_is_logged_by_what_was_typed_without_the_password(): void
    {
        $this->post('/login', ['email' => 'salah@example.com', 'password' => 'rahasia-sekali']);

        $log = ActivityLog::where('action', 'login')->firstOrFail();
        $this->assertFalse($log->succeeded);
        $this->assertSame('salah@example.com', $log->actor_name);
        $this->assertStringNotContainsString('rahasia-sekali', json_encode($log->toArray()));
    }

    public function test_alumni_activity_goes_to_the_alumni_log(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A001XYZ']);
        $user = $this->userWithRole('Alumni', ['nim' => $alumni->nim]);

        $this->actingAs($user)->put(route('tracer.update', $alumni), ['f8' => 2]);

        $this->assertDatabaseHas('activity_logs', [
            'category' => 'alumni',
            'user_id' => $user->id,
            'alumni_id' => $alumni->id,
            'action' => 'tracer.update',
            'description' => 'Mengisi/memperbarui kuesioner tracer',
        ]);
    }

    public function test_a_pengguna_alumni_filling_the_public_form_goes_to_the_alumni_log(): void
    {
        $alumni = Alumni::factory()->create();
        $url = URL::temporarySignedRoute('employer.show', now()->addDays(30), ['alumni' => $alumni->id]);

        $this->get($url);
        $this->post($url, [
            'nama_pengisi' => 'Budi Santoso',
            'nama_perusahaan' => 'PT Contoh',
            'q1_kerja_sama_tim' => 1, 'q2_pengembangan_diri' => 1, 'q3_komunikasi' => 1,
            'q4_teknologi_informasi' => 1, 'q5_bahasa_asing' => 1, 'q6_keahlian' => 1, 'q7_integritas' => 1,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'category' => 'alumni', 'action' => 'employer.show', 'alumni_id' => $alumni->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'category' => 'alumni',
            'action' => 'employer.store',
            'actor_name' => 'Budi Santoso',
            'actor_role' => 'Pengguna Alumni',
            'alumni_id' => $alumni->id,
            'succeeded' => true,
        ]);
    }

    public function test_a_change_rejected_by_validation_is_logged_as_failed(): void
    {
        $admin = $this->userWithRole('Super Admin');

        $this->actingAs($admin)->post(route('admin.faculties.store'), []);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'admin.faculties.store', 'description' => 'Menambah fakultas', 'succeeded' => false,
        ]);
    }

    public function test_plain_page_views_are_not_logged(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'))->get(route('dashboard'))->assertOk();

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_actions_taken_while_impersonating_name_the_super_admin(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Admin Universitas');

        $this->actingAs($admin)->post(route('admin.users.impersonate', $target));
        $this->post(route('admin.faculties.store'), ['code' => 'Z', 'name' => 'Fakultas Uji']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'admin.users.impersonate', 'user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'admin.faculties.store', 'user_id' => $target->id, 'impersonator_id' => $admin->id,
        ]);
    }

    public function test_deleting_your_own_account_is_still_logged(): void
    {
        $user = $this->userWithRole('Surveyor', ['faculty_id' => Faculty::factory()->create()->id]);

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password']);

        $this->assertDatabaseHas('activity_logs', ['action' => 'profile.destroy', 'user_id' => null, 'actor_name' => $user->name]);
    }

    public function test_only_super_admin_can_view_the_log_and_each_tab_shows_its_own_category(): void
    {
        ActivityLog::create(['category' => 'admin', 'actor_name' => 'Admin Satu', 'action' => 'login', 'description' => 'Login']);
        ActivityLog::create(['category' => 'alumni', 'actor_name' => 'Alumni Dua', 'action' => 'login.nim', 'description' => 'Login alumni']);

        $this->actingAs($this->userWithRole('Admin Universitas'))
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();

        $superAdmin = $this->userWithRole('Super Admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('Admin Satu')
            ->assertDontSee('Alumni Dua');

        $this->actingAs($superAdmin)
            ->get(route('admin.activity-logs.index', ['kategori' => 'alumni']))
            ->assertOk()
            ->assertSee('Alumni Dua')
            ->assertDontSee('Admin Satu');
    }
}
