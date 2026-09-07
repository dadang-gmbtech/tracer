<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramStudyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_universitas_can_create_a_program_studi(): void
    {
        $faculty = Faculty::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('admin.program-studies.store'), [
            'faculty_id' => $faculty->id,
            'code' => '55201',
            'name' => 'Informatika',
            'level' => 'S1',
        ]);

        $response->assertRedirect(route('admin.program-studies.index'));
        $this->assertDatabaseHas('program_studies', ['code' => '55201', 'name' => 'Informatika']);
    }

    public function test_creating_a_program_studi_with_a_duplicate_code_is_rejected(): void
    {
        $faculty = Faculty::factory()->create();
        StudyProgram::factory()->create(['code' => '55201', 'faculty_id' => $faculty->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('admin.program-studies.store'), [
            'faculty_id' => $faculty->id,
            'code' => '55201',
            'name' => 'Percobaan',
            'level' => 'S1',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_universitas_can_update_a_program_studi_keeping_its_own_code(): void
    {
        // Regression: the unique rule used to be built as a raw string
        // ('unique:program_studies,code,'.($programStudy?->id)) — on create
        // that left a trailing empty "ignore id" segment, which Postgres
        // rejects when comparing it to the bigint id column (SQLSTATE
        // 22P02); MySQL/SQLite silently tolerate it. Rule::unique()->ignore()
        // must not regress the update path: saving a record's own unchanged
        // code must still succeed.
        $faculty = Faculty::factory()->create();
        $programStudy = StudyProgram::factory()->create(['code' => '55201', 'faculty_id' => $faculty->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->put(route('admin.program-studies.update', $programStudy), [
            'faculty_id' => $faculty->id,
            'code' => '55201',
            'name' => 'Nama Baru',
            'level' => 'S1',
        ]);

        $response->assertRedirect(route('admin.program-studies.index'));
        $this->assertDatabaseHas('program_studies', ['id' => $programStudy->id, 'name' => 'Nama Baru', 'code' => '55201']);
    }

    public function test_updating_a_program_studi_to_another_ones_code_is_rejected(): void
    {
        $faculty = Faculty::factory()->create();
        StudyProgram::factory()->create(['code' => '55201', 'faculty_id' => $faculty->id]);
        $target = StudyProgram::factory()->create(['code' => '55202', 'faculty_id' => $faculty->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->put(route('admin.program-studies.update', $target), [
            'faculty_id' => $faculty->id,
            'code' => '55201',
            'name' => $target->name,
            'level' => 'S1',
        ]);

        $response->assertSessionHasErrors('code');
    }
}
