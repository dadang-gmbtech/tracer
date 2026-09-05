<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faculty>
 */
class FacultyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('?'),
            'name' => fake()->unique()->words(3, true),
        ];
    }
}
