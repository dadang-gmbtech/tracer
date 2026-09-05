<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\City;
use App\Models\Faculty;
use App\Models\Province;
use App\Models\Question;
use App\Models\StudyProgram;
use App\Models\TracerResponse;
use App\Models\UmpSalary;
use App\Models\User;
use Database\Seeders\ProvinceCitySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every top-level page as Super Admin to catch Blade/route mistakes
 * (undefined variables, wrong route() names, missing view files) that a
 * narrower policy/validation test wouldn't exercise.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_every_main_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ProvinceCitySeeder::class);

        $faculty = Faculty::factory()->create();
        $studyProgram = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $alumni = Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $studyProgram->id]);
        $province = Province::first();
        $city = City::factory()->create(['province_id' => $province->id]);
        $ump = UmpSalary::factory()->create(['province_id' => $province->id]);
        $question = Question::factory()->create();

        // A fully-populated response exercises the tracer edit form's data
        // binding (old()/$response->attr, Alpine x-data JSON) with real values.
        $filledAlumni = Alumni::factory()->create(['faculty_id' => $faculty->id, 'program_study_id' => $studyProgram->id]);
        TracerResponse::factory()->create([
            'alumni_id' => $filledAlumni->id,
            'work_province_id' => $province->id,
            'work_city_id' => $city->id,
            'f5c' => 1,
            'f5d' => 2,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin);

        $pages = [
            route('dashboard'),
            route('alumni.index'),
            route('alumni.show', $alumni),
            route('tracer.edit', $alumni),
            route('tracer.edit', $filledAlumni),
            route('reports.index'),
            route('reports.create'),
            route('admin.users.index'),
            route('admin.users.create'),
            route('admin.users.edit', $admin),
            route('admin.users.import.form'),
            route('admin.faculties.index'),
            route('admin.faculties.create'),
            route('admin.faculties.edit', $faculty),
            route('admin.program-studies.index'),
            route('admin.program-studies.create'),
            route('admin.program-studies.edit', $studyProgram),
            route('admin.provinces.index'),
            route('admin.provinces.create'),
            route('admin.provinces.edit', $province),
            route('admin.cities.index'),
            route('admin.cities.create'),
            route('admin.cities.edit', $city),
            route('admin.ump.index'),
            route('admin.ump.create'),
            route('admin.ump.edit', $ump),
            route('admin.questions.index'),
            route('admin.questions.create'),
            route('admin.questions.edit', $question),
        ];

        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }
    }
}
