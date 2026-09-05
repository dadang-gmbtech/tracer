<?php

namespace Database\Factories;

use App\Models\Faculty;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyProgram>
 */
class StudyProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'faculty_id' => Faculty::factory(),
            'code' => fake()->unique()->numerify('#####'),
            'name' => fake()->unique()->words(2, true),
            'level' => 'S1',
        ];
    }
}
