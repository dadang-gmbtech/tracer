<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeDuplicateFacultiesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_merges_duplicates_into_the_faculty_that_already_has_a_real_name(): void
    {
        $bareA = Faculty::factory()->create(['code' => 'A', 'name' => 'A']);
        $realA = Faculty::factory()->create(['code' => 'A ', 'name' => 'Pertanian']);
        $anotherBareA = Faculty::factory()->create(['code' => "A\u{A0}", 'name' => 'A']);

        $prodi = StudyProgram::factory()->create(['faculty_id' => $bareA->id]);
        // Pin program_study_id too — Alumni's factory otherwise spins up its
        // own unrelated StudyProgram/Faculty pair, which would pollute the
        // faculties table with a row that has nothing to do with this test.
        $alumni = Alumni::factory()->create(['faculty_id' => $anotherBareA->id, 'program_study_id' => $prodi->id]);
        $admin = User::factory()->create(['faculty_id' => $bareA->id]);

        $this->artisan('app:merge-duplicate-faculties')->assertSuccessful();

        $this->assertDatabaseHas('faculties', ['id' => $realA->id, 'code' => 'A', 'name' => 'Pertanian']);
        $this->assertDatabaseMissing('faculties', ['id' => $bareA->id]);
        $this->assertDatabaseMissing('faculties', ['id' => $anotherBareA->id]);

        $this->assertSame($realA->id, $prodi->fresh()->faculty_id);
        $this->assertSame($realA->id, $alumni->fresh()->faculty_id);
        $this->assertSame($realA->id, $admin->fresh()->faculty_id);
        $this->assertSame(1, Faculty::count());
    }

    public function test_when_no_duplicate_has_a_real_name_the_oldest_row_is_kept_and_renamed(): void
    {
        $first = Faculty::factory()->create(['code' => 'H', 'name' => 'H']);
        // The second row's raw code has trailing whitespace its own name
        // lacks ("H" !== "H ") — must still be recognized as a bare-code
        // name (compared against the *normalized* code), not mistaken for
        // a real one.
        Faculty::factory()->create(['code' => 'H ', 'name' => 'H']);

        $this->artisan('app:merge-duplicate-faculties')->assertSuccessful();

        $this->assertSame(1, Faculty::count());
        $this->assertDatabaseHas('faculties', ['id' => $first->id, 'code' => 'H', 'name' => 'Teknik']);
    }

    public function test_dry_run_reports_without_saving(): void
    {
        Faculty::factory()->create(['code' => 'A', 'name' => 'A']);
        Faculty::factory()->create(['code' => 'A ', 'name' => 'A']);

        $this->artisan('app:merge-duplicate-faculties', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(2, Faculty::count());
    }

    public function test_faculties_with_distinct_codes_are_left_alone(): void
    {
        Faculty::factory()->create(['code' => 'A', 'name' => 'Pertanian']);
        Faculty::factory()->create(['code' => 'B', 'name' => 'Biologi']);

        $this->artisan('app:merge-duplicate-faculties')->assertSuccessful();

        $this->assertSame(2, Faculty::count());
    }
}
