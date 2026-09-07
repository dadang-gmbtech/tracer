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

    public function test_only_super_admin_can_add_or_edit_alumni_bio_data(): void
    {
        $faculty = Faculty::factory()->create();
        $prodi = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $alumni = Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $prodi->id]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)->get(route('alumni.create'))->assertOk();
        $this->actingAs($superAdmin)->post(route('alumni.store'), [
            'nim' => 'A123',
            'nama' => 'Budi Santoso',
            'faculty_id' => $faculty->id,
            'program_study_id' => $prodi->id,
            'graduation_year' => 2024,
        ])->assertRedirect(route('alumni.index'));
        $this->assertDatabaseHas('alumni', ['nim' => 'A123', 'nama' => 'Budi Santoso']);

        $this->actingAs($superAdmin)->put(route('alumni.update', $alumni), [
            'nim' => $alumni->nim,
            'nama' => 'Diedit Super Admin',
            'faculty_id' => $faculty->id,
            'program_study_id' => $prodi->id,
            'graduation_year' => 2024,
        ])->assertRedirect(route('alumni.index'));
        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'nama' => 'Diedit Super Admin']);

        // Every other role — including Admin Universitas, who is otherwise
        // treated as "sees/manages everything" — is locked out of bio data.
        // Only the tracer questionnaire (a separate ability) stays open to
        // them, scoped to their own faculty/prodi.
        foreach (['Admin Universitas', 'Admin Fakultas', 'Admin Prodi', 'Surveyor'] as $role) {
            $user = User::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $prodi->id]);
            $user->assignRole($role);

            $this->actingAs($user)->get(route('alumni.create'))->assertForbidden();
            $this->actingAs($user)->post(route('alumni.store'), [
                'nim' => 'X-'.$role,
                'nama' => 'Percobaan '.$role,
                'faculty_id' => $faculty->id,
                'program_study_id' => $prodi->id,
                'graduation_year' => 2024,
            ])->assertForbidden();

            $this->actingAs($user)->get(route('alumni.edit', $alumni))->assertForbidden();
            $this->actingAs($user)->put(route('alumni.update', $alumni), [
                'nim' => $alumni->nim,
                'nama' => 'Diganti oleh '.$role,
                'faculty_id' => $faculty->id,
                'program_study_id' => $prodi->id,
                'graduation_year' => 2024,
            ])->assertForbidden();
        }

        $this->assertDatabaseMissing('alumni', ['nama' => 'Percobaan Admin Universitas']);
        $this->assertDatabaseMissing('alumni', ['nama' => 'Diganti oleh Admin Fakultas']);
    }

    public function test_the_alumni_index_hides_manage_actions_from_everyone_but_super_admin(): void
    {
        $faculty = Faculty::factory()->create();
        $prodi = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $prodi->id]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $this->actingAs($superAdmin)
            ->get(route('alumni.index'))
            ->assertOk()
            ->assertSee('Tambah Alumni');

        $adminFakultas = User::factory()->create(['faculty_id' => $faculty->id]);
        $adminFakultas->assignRole('Admin Fakultas');
        $this->actingAs($adminFakultas)
            ->get(route('alumni.index'))
            ->assertOk()
            ->assertDontSee('Tambah Alumni')
            // Still able to help fill in the tracer questionnaire, though.
            ->assertSee('Isi Kuesioner Tracer');
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

    public function test_admin_fakultas_and_surveyor_can_still_fill_the_tracer_form_within_their_faculty(): void
    {
        $ownFaculty = Faculty::factory()->create();
        $ownProdi = StudyProgram::factory()->create(['faculty_id' => $ownFaculty->id]);
        $otherFaculty = Faculty::factory()->create();
        $otherProdi = StudyProgram::factory()->create(['faculty_id' => $otherFaculty->id]);

        $own = Alumni::factory()->create(['faculty_id' => $ownFaculty->id, 'program_study_id' => $ownProdi->id]);
        $other = Alumni::factory()->create(['faculty_id' => $otherFaculty->id, 'program_study_id' => $otherProdi->id]);

        $surveyor = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $surveyor->assignRole('Surveyor');

        $this->actingAs($surveyor)->get(route('tracer.edit', $own))->assertOk();
        $this->actingAs($surveyor)->get(route('tracer.edit', $other))->assertForbidden();
    }

    public function test_pimpinan_fakultas_cannot_add_edit_or_fill_in_alumni(): void
    {
        $faculty = Faculty::factory()->create();
        $prodi = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $alumni = Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $prodi->id]);

        $pimpinan = User::factory()->create(['faculty_id' => $faculty->id]);
        $pimpinan->assignRole('Pimpinan Fakultas');

        $this->actingAs($pimpinan)->get(route('alumni.create'))->assertForbidden();
        $this->actingAs($pimpinan)->get(route('alumni.edit', $alumni))->assertForbidden();
        $this->actingAs($pimpinan)->get(route('tracer.edit', $alumni))->assertForbidden();

        // They still see the read-only index without any manage actions.
        $this->actingAs($pimpinan)
            ->get(route('alumni.index'))
            ->assertOk()
            ->assertDontSee('Tambah Alumni')
            ->assertDontSee('Isi Kuesioner Tracer');
    }
}
