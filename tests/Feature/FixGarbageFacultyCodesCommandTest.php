<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixGarbageFacultyCodesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_a_garbage_faculty_via_its_own_bare_letter_name(): void
    {
        $real = Faculty::factory()->create(['code' => 'L', 'name' => 'Perikanan dan Ilmu Kelautan']);
        $garbage = Faculty::factory()->create(['code' => 'yasinta.anggararatri@mhs.unsoed.ac.id', 'name' => 'L']);
        $prodi = StudyProgram::factory()->create(['faculty_id' => $garbage->id]);
        $alumni = Alumni::factory()->create(['faculty_id' => $garbage->id, 'program_study_id' => $prodi->id, 'nim' => 'L1D019001']);

        $this->artisan('app:fix-garbage-faculty-codes')->assertSuccessful();

        $this->assertDatabaseMissing('faculties', ['id' => $garbage->id]);
        $this->assertSame($real->id, $prodi->fresh()->faculty_id);
        $this->assertSame($real->id, $alumni->fresh()->faculty_id);
    }

    public function test_it_falls_back_to_guessing_from_the_attached_alumnis_nim_when_the_name_is_unusable(): void
    {
        $real = Faculty::factory()->create(['code' => 'H', 'name' => 'Teknik']);
        $garbage = Faculty::factory()->create(['code' => 'someone@mhs.unsoed.ac.id', 'name' => 'Fakultas Tidak Diketahui']);
        $alumni = Alumni::factory()->create(['faculty_id' => $garbage->id, 'nim' => 'H1D019099']);

        $this->artisan('app:fix-garbage-faculty-codes')->assertSuccessful();

        $this->assertDatabaseMissing('faculties', ['id' => $garbage->id]);
        $this->assertSame($real->id, $alumni->fresh()->faculty_id);
    }

    public function test_a_garbage_faculty_that_cannot_be_resolved_is_left_alone(): void
    {
        $garbage = Faculty::factory()->create(['code' => 'someone@mhs.unsoed.ac.id', 'name' => 'Fakultas Tidak Diketahui']);

        $this->artisan('app:fix-garbage-faculty-codes')->assertSuccessful();

        $this->assertDatabaseHas('faculties', ['id' => $garbage->id]);
    }

    public function test_faculties_with_a_normal_short_code_are_left_alone(): void
    {
        $faculty = Faculty::factory()->create(['code' => 'A', 'name' => 'Pertanian']);

        $this->artisan('app:fix-garbage-faculty-codes')->assertSuccessful();

        $this->assertDatabaseHas('faculties', ['id' => $faculty->id, 'code' => 'A']);
    }

    public function test_dry_run_reports_without_saving(): void
    {
        $garbage = Faculty::factory()->create(['code' => 'someone@mhs.unsoed.ac.id', 'name' => 'L']);
        Faculty::factory()->create(['code' => 'L', 'name' => 'Perikanan dan Ilmu Kelautan']);

        $this->artisan('app:fix-garbage-faculty-codes', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('faculties', ['id' => $garbage->id]);
    }
}
