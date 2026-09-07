<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\City;
use App\Models\Faculty;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TracerFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_alumni_can_view_and_submit_their_own_tracer_form(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A001XYZ']);
        $province = Province::factory()->create();
        $city = City::factory()->create(['province_id' => $province->id]);

        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)
            ->get(route('tracer.edit', $alumni))
            ->assertOk()
            ->assertSee($alumni->nim)
            ->assertSee($alumni->nama)
            ->assertSee($alumni->nik ?? '-');

        $response = $this->actingAs($user)->put(route('tracer.update', $alumni), [
            'f8' => 1,
            'f502' => 2,
            'f505' => 4_500_000,
            'work_province_id' => $province->id,
            'work_city_id' => $city->id,
            'f1101' => 3,
            'f1201' => 1,
            'f14' => 1,
            'f15' => 2,
            'f301' => 3,
            ...array_fill_keys(array_map(fn ($c) => "f{$c}", [
                1761, 1762, 1763, 1764, 1765, 1766, 1767, 1768, 1769, 1770, 1771, 1772, 1773, 1774,
            ]), 4),
        ]);

        $response->assertRedirect(route('alumni.show', $alumni));
        $this->assertDatabaseHas('tracer_responses', [
            'alumni_id' => $alumni->id,
            'f8' => 1,
            'work_province_id' => $province->id,
            'f5a1' => $province->code,
        ]);
    }

    public function test_alumni_lands_on_their_own_tracer_form_right_after_logging_in(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A002XYZ']);
        $user = User::factory()->create(['nim' => $alumni->nim, 'tanggal_lahir' => '2000-01-01']);
        $user->assignRole('Alumni');

        $response = $this->post(route('login.nim'), [
            'nim' => $alumni->nim,
            'tanggal_lahir' => '2000-01-01',
        ]);

        $response->assertRedirect(route('tracer.edit', $alumni));
    }

    public function test_an_inactive_alumni_cannot_log_in_via_nim_and_tanggal_lahir(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A003XYZ']);
        $user = User::factory()->create(['nim' => $alumni->nim, 'tanggal_lahir' => '2000-01-01', 'status' => 'inactive']);
        $user->assignRole('Alumni');

        $response = $this->post(route('login.nim'), [
            'nim' => $alumni->nim,
            'tanggal_lahir' => '2000-01-01',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('nim');
    }

    public function test_alumni_cannot_fill_tracer_form_for_another_alumni(): void
    {
        $alumni = Alumni::factory()->create();
        $otherAlumni = Alumni::factory()->create();

        $user = User::factory()->create(['nim' => $otherAlumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)
            ->get(route('tracer.edit', $alumni))
            ->assertForbidden();
    }

    public function test_admin_fakultas_cannot_fill_tracer_for_alumni_outside_their_faculty(): void
    {
        $ownFaculty = Faculty::factory()->create();
        $otherFaculty = Faculty::factory()->create();
        $alumni = Alumni::factory()->create(['faculty_id' => $otherFaculty->id]);

        $admin = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $admin->assignRole('Admin Fakultas');

        $this->actingAs($admin)
            ->get(route('tracer.edit', $alumni))
            ->assertForbidden();
    }
}
