<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->countryCode(),
            'name' => fake()->unique()->country(),
        ];
    }
}
