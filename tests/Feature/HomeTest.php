<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_guest_sees_the_public_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Tracer Studi')
            ->assertSee('Isi Kuesioner Sekarang');
    }

    public function test_the_landing_page_does_not_show_tracer_statistics(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Statistik Partisipasi Alumni');
    }

    public function test_a_logged_in_staff_user_is_redirected_to_the_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_a_logged_in_alumni_is_redirected_to_their_tracer_form(): void
    {
        $alumni = Alumni::factory()->create();
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)->get('/')->assertRedirect(route('tracer.edit', $alumni));
    }
}
