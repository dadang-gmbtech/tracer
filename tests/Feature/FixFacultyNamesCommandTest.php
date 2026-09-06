<?php

namespace Tests\Feature;

use App\Models\Faculty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixFacultyNamesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renames_faculties_whose_name_is_still_their_bare_code(): void
    {
        Faculty::factory()->create(['code' => 'A', 'name' => 'A']);
        Faculty::factory()->create(['code' => 'H', 'name' => 'H']);
        // Already has a real name — must be left untouched.
        $untouched = Faculty::factory()->create(['code' => 'B', 'name' => 'Biologi']);
        // Not in the known UNSOED directory — left untouched rather than guessed.
        $unknown = Faculty::factory()->create(['code' => 'Z', 'name' => 'Z']);

        $this->artisan('app:fix-faculty-names')->assertSuccessful();

        $this->assertDatabaseHas('faculties', ['code' => 'A', 'name' => 'Pertanian']);
        $this->assertDatabaseHas('faculties', ['code' => 'H', 'name' => 'Teknik']);
        $this->assertDatabaseHas('faculties', ['id' => $untouched->id, 'name' => 'Biologi']);
        $this->assertDatabaseHas('faculties', ['id' => $unknown->id, 'name' => 'Z']);
    }

    public function test_dry_run_reports_changes_without_saving_them(): void
    {
        Faculty::factory()->create(['code' => 'A', 'name' => 'A']);

        $this->artisan('app:fix-faculty-names', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('faculties', ['code' => 'A', 'name' => 'A']);
    }
}
