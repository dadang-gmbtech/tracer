<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'target' => Question::TARGET_TRACER,
            'label' => fake()->sentence(),
            'type' => 'text',
            'options' => null,
            'order' => 0,
            'is_active' => true,
        ];
    }
}
