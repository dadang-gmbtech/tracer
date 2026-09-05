<?php

namespace Database\Factories;

use App\Models\Province;
use App\Models\UmpSalary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UmpSalary>
 */
class UmpSalaryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'province_id' => Province::factory(),
            'year' => now()->year,
            'amount' => fake()->numberBetween(2_000_000, 5_000_000),
        ];
    }
}
