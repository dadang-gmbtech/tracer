<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\TracerResponse;
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

    public function test_the_landing_page_shows_aggregate_response_statistics_per_faculty(): void
    {
        $faculty = Faculty::factory()->create(['name' => 'Fakultas Pertanian']);
        $responded = Alumni::factory()->create(['faculty_id' => $faculty->id]);
        Alumni::factory()->create(['faculty_id' => $faculty->id]);
        TracerResponse::factory()->create(['alumni_id' => $responded->id]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Fakultas Pertanian');
        $response->assertSee('50%'); // 1 of 2 alumni in the faculty responded
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
