<?php

namespace Database\Factories;

use App\Models\Alumni;
use App\Models\TracerResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TracerResponse>
 */
class TracerResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alumni_id' => Alumni::factory(),
            'f8' => 1,
            'f502' => 3,
            'f505' => 4_000_000,
            'f1201' => 1,
            'f301' => 3,
            'submitted_at' => now(),
        ];
    }
}
