<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\TracerResponse;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_sees_the_alumni_nav_link(): void
    {
        // Super Admin passes every Gate check via a Gate::before bypass, not
        // by matching a role name — a nav check built on hasAnyRole() instead
        // of the policy would (and once did) hide the link from them.
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('alumni.index'), false);
    }

    public function test_super_admin_and_admin_universitas_can_edit_an_alumnus_in_any_faculty(): void
    {
        $faculty = Faculty::factory()->create();
        $prodi = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $alumni = Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $prodi->id]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)->put(route('alumni.update', $alumni), [
            'nim' => $alumni->nim,
            'nama' => 'Diedit Super Admin',
            'faculty_id' => $faculty->id,
            'program_study_id' => $prodi->id,
            'graduation_year' => 2024,
        ])->assertRedirect(route('alumni.index'));
        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'nama' => 'Diedit Super Admin']);

        $adminUniversitas = User::factory()->create();
        $adminUniversitas->assignRole('Admin Universitas');

        $this->actingAs($adminUniversitas)->put(route('alumni.update', $alumni), [
            'nim' => $alumni->nim,
            'nama' => 'Diedit Admin Universitas',
            'faculty_id' => $faculty->id,
            'program_study_id' => $prodi->id,
            'graduation_year' => 2024,
        ])->assertRedirect(route('alumni.index'));
        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'nama' => 'Diedit Admin Universitas']);
    }

    public function test_the_alumni_index_shows_faculty_jenjang_and_status_filters(): void
    {
        $faculty = Faculty::factory()->create();
        $prodi = StudyProgram::factory()->create(['faculty_id' => $faculty->id, 'level' => 'S1']);
        $filled = Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $prodi->id]);
        $unfilled = Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $prodi->id]);
        TracerResponse::factory()->create(['alumni_id' => $filled->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)
            ->get(route('alumni.index', ['status' => 'sudah']))
            ->assertOk()
            ->assertSee($filled->nama)
            ->assertDontSee($unfilled->nama);

        $this->actingAs($admin)
            ->get(route('alumni.index', ['status' => 'belum']))
            ->assertOk()
            ->assertDontSee($filled->nama)
            ->assertSee($unfilled->nama);
    }

    public function test_admin_universitas_can_add_an_alumnus_in_any_faculty(): void
    {
        $faculty = Faculty::factory()->create();
        $prodi = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('alumni.store'), [
            'nim' => 'A123',
            'nama' => 'Budi Santoso',
            'faculty_id' => $faculty->id,
            'program_study_id' => $prodi->id,
            'graduation_year' => 2024,
        ])->assertRedirect(route('alumni.index'));

        $this->assertDatabaseHas('alumni', ['nim' => 'A123', 'nama' => 'Budi Santoso']);
    }

    public function test_admin_fakultas_can_only_add_alumni_within_their_own_faculty(): void
    {
        $ownFaculty = Faculty::factory()->create();
        $ownProdi = StudyProgram::factory()->create(['faculty_id' => $ownFaculty->id]);
        $otherFaculty = Faculty::factory()->create();
        $otherProdi = StudyProgram::factory()->create(['faculty_id' => $otherFaculty->id]);

        $admin = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $admin->assignRole('Admin Fakultas');

        // Tries to sneak an alumnus into a different faculty's program studi.
        $this->actingAs($admin)->post(route('alumni.store'), [
            'nim' => 'B456',
            'nama' => 'Siti Aminah',
            'faculty_id' => $otherFaculty->id,
            'program_study_id' => $otherProdi->id,
            'graduation_year' => 2024,
        ])->assertSessionHasErrors('program_study_id');

        $this->assertDatabaseMissing('alumni', ['nim' => 'B456']);

        // Their own faculty's program studi works, and faculty_id is forced
        // server-side even though it wasn't part of what we need to submit.
        $this->actingAs($admin)->post(route('alumni.store'), [
            'nim' => 'C789',
            'nama' => 'Andi Wijaya',
            'program_study_id' => $ownProdi->id,
            'graduation_year' => 2024,
        ])->assertRedirect(route('alumni.index'));

        $this->assertDatabaseHas('alumni', ['nim' => 'C789', 'faculty_id' => $ownFaculty->id]);
    }

    public function test_surveyor_cannot_edit_an_alumnus_outside_their_faculty(): void
    {
        $ownFaculty = Faculty::factory()->create();
        $otherFaculty = Faculty::factory()->create();
        $otherProdi = StudyProgram::factory()->create(['faculty_id' => $otherFaculty->id]);
        $alumni = Alumni::factory()->create(['faculty_id' => $otherFaculty->id, 'program_study_id' => $otherProdi->id]);

        $surveyor = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $surveyor->assignRole('Surveyor');

        $this->actingAs($surveyor)->get(route('alumni.edit', $alumni))->assertForbidden();
        $this->actingAs($surveyor)->put(route('alumni.update', $alumni), [
            'nim' => $alumni->nim,
            'nama' => 'Diganti Paksa',
            'graduation_year' => 2024,
        ])->assertForbidden();
    }

    public function test_pimpinan_fakultas_cannot_add_or_edit_alumni(): void
    {
        $faculty = Faculty::factory()->create();
        $prodi = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $alumni = Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $prodi->id]);

        $pimpinan = User::factory()->create(['faculty_id' => $faculty->id]);
        $pimpinan->assignRole('Pimpinan Fakultas');

        $this->actingAs($pimpinan)->get(route('alumni.create'))->assertForbidden();
        $this->actingAs($pimpinan)->get(route('alumni.edit', $alumni))->assertForbidden();

        // They still see the read-only index without the manage actions.
        $this->actingAs($pimpinan)
            ->get(route('alumni.index'))
            ->assertOk()
            ->assertDontSee('Tambah Alumni');
    }
}
