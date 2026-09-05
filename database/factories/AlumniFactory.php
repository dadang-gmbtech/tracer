<?php

namespace Database\Factories;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alumni>
 */
class AlumniFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nim' => fake()->unique()->numerify('A1A###???'),
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'faculty_id' => Faculty::factory(),
            'program_study_id' => StudyProgram::factory(),
            'graduation_year' => fake()->numberBetween(2020, 2025),
            'phone' => fake()->numerify('08##########'),
        ];
    }
}
