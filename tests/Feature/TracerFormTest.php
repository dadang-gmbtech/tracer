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
            'f5b' => 'PT Contoh Sejahtera',
            'f5d' => 1,
            'f1201' => 1,
            'f14' => 1,
            'f15' => 2,
            'f301' => 3,
            'f404' => 1,
            'f6' => 5,
            'f7' => 2,
            'f7a' => 1,
            'f1001' => 1,
            'f1601' => 1,
            ...array_fill_keys(array_map(fn ($c) => "f{$c}", [
                ...range(1761, 1782), ...range(21, 37),
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

    public function test_submitting_the_bekerja_branch_without_its_required_fields_is_rejected(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A004XYZ']);
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $response = $this->actingAs($user)->put(route('tracer.update', $alumni), [
            'f8' => 1,
            'f1201' => 1,
            ...array_fill_keys(array_map(fn ($c) => "f{$c}", [
                ...range(1761, 1782), ...range(21, 37),
            ]), 4),
        ]);

        $response->assertSessionHasErrors([
            'f502', 'f505', 'work_province_id', 'work_city_id', 'f1101', 'f5b', 'f5d', 'f14', 'f15', 'f301',
        ]);
    }

    public function test_alumni_can_submit_the_wirausaha_branch(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A005XYZ']);
        $province = Province::factory()->create();
        $city = City::factory()->create(['province_id' => $province->id]);
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $response = $this->actingAs($user)->put(route('tracer.update', $alumni), [
            'f8' => 3,
            'f502' => 1,
            'f505' => 3_000_000,
            'f5c' => 1,
            'f5b' => 'Toko Online Saya',
            'f5e' => 1,
            'f5d' => 1,
            'work_province_id' => $province->id,
            'work_city_id' => $city->id,
            'f14' => 1,
            'f15' => 2,
            'f301' => 1,
            'f302' => 2,
            'f1606' => 1,
            'f1201' => 1,
            ...array_fill_keys(array_map(fn ($c) => "f{$c}", [
                ...range(1761, 1782), ...range(21, 37),
            ]), 4),
        ]);

        $response->assertRedirect(route('alumni.show', $alumni));
        $this->assertDatabaseHas('tracer_responses', [
            'alumni_id' => $alumni->id,
            'f8' => 3,
            'f5c' => 1,
            'f5e' => 1,
        ]);
    }

    public function test_wirausaha_branch_rejects_the_retired_staff_option_for_f5c(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A006XYZ']);
        $province = Province::factory()->create();
        $city = City::factory()->create(['province_id' => $province->id]);
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $response = $this->actingAs($user)->put(route('tracer.update', $alumni), [
            'f8' => 3,
            'f502' => 1,
            'f505' => 3_000_000,
            'f5c' => 3, // "Staff" — no longer a valid option on the new form
            'f5b' => 'Toko Online Saya',
            'f5e' => 1,
            'f5d' => 1,
            'work_province_id' => $province->id,
            'work_city_id' => $city->id,
            'f14' => 1,
            'f15' => 2,
            'f301' => 1,
            'f302' => 2,
            'f1606' => 1,
            'f1201' => 1,
            ...array_fill_keys(array_map(fn ($c) => "f{$c}", [
                ...range(1761, 1782), ...range(21, 37),
            ]), 4),
        ]);

        $response->assertSessionHasErrors('f5c');
    }

    public function test_alumni_can_submit_the_melanjutkan_pendidikan_branch(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A007XYZ']);
        $province = Province::factory()->create();
        $city = City::factory()->create(['province_id' => $province->id]);
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $response = $this->actingAs($user)->put(route('tracer.update', $alumni), [
            'f8' => 4,
            'f18a' => 2,
            'f18b' => 'Universitas Gadjah Mada',
            'f18c' => 'Magister Manajemen',
            'f18d' => '2026-02-01',
            'work_province_id' => $province->id,
            'work_city_id' => $city->id,
            'f14' => 1,
            'f1201' => 1,
            ...array_fill_keys(array_map(fn ($c) => "f{$c}", [
                ...range(1761, 1782), ...range(21, 37),
            ]), 4),
        ]);

        $response->assertRedirect(route('alumni.show', $alumni));
        $this->assertDatabaseHas('tracer_responses', [
            'alumni_id' => $alumni->id,
            'f8' => 4,
            'f18b' => 'Universitas Gadjah Mada',
        ]);

        // f15 doesn't exist for this branch — must not be required.
        $response->assertSessionDoesntHaveErrors('f15');
    }

    public function test_alumni_can_submit_the_mencari_kerja_branch(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A008XYZ']);
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $response = $this->actingAs($user)->put(route('tracer.update', $alumni), [
            'f8' => 5,
            'f301' => 1,
            'f302' => 3,
            'f404' => 1,
            'f6' => 4,
            'f7' => 1,
            'f7a' => 0,
            'f1001' => 2,
            'f1201' => 1,
            ...array_fill_keys(array_map(fn ($c) => "f{$c}", [
                ...range(1761, 1782), ...range(21, 37),
            ]), 3),
        ]);

        $response->assertRedirect(route('alumni.show', $alumni));
        $this->assertDatabaseHas('tracer_responses', ['alumni_id' => $alumni->id, 'f8' => 5]);
    }

    public function test_belum_memungkinkan_bekerja_branch_only_needs_the_universal_fields(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A009XYZ']);
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $response = $this->actingAs($user)->put(route('tracer.update', $alumni), [
            'f8' => 2,
            'f1201' => 1,
            ...array_fill_keys(array_map(fn ($c) => "f{$c}", [
                ...range(1761, 1782), ...range(21, 37),
            ]), 3),
        ]);

        $response->assertRedirect(route('alumni.show', $alumni));
        $this->assertDatabaseHas('tracer_responses', ['alumni_id' => $alumni->id, 'f8' => 2]);
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

    public function test_the_tracer_form_shows_the_button_to_generate_a_pengguna_alumni_link(): void
    {
        // Alumni land on this page straight after login (see
        // User::postLoginUrl()) and never see alumni.show at all, so the
        // "buat tautan" action needs to live here, not just on alumni.show.
        $alumni = Alumni::factory()->create(['nim' => 'A1A002XYZ']);
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)
            ->get(route('tracer.edit', $alumni))
            ->assertOk()
            ->assertSee('Buat Tautan Form Pengguna Alumni');
    }

    public function test_generating_a_pengguna_alumni_link_redirects_back_to_the_tracer_form_with_the_link(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A003XYZ']);
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $response = $this->actingAs($user)->post(route('tracer.share-link', $alumni));

        $response->assertRedirect(route('tracer.edit', $alumni));
        $this->assertNotNull($response->getSession()->get('employerLink'));

        $this->actingAs($user)
            ->withSession(['employerLink' => $response->getSession()->get('employerLink')])
            ->get(route('tracer.edit', $alumni))
            ->assertOk()
            ->assertSee('Salin Link');
    }
}
