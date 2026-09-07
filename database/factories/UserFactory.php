<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // The users.status column defaults to 'active' at the DB level,
            // but a freshly ->create()'d Eloquent instance doesn't pick up a
            // DB-generated default into its in-memory attributes — so
            // $user->isActive() reads a missing/null attribute as false even
            // though the stored row is genuinely 'active'. Set it explicitly
            // so factory-made users behave the same as every real signup
            // path (which all already set this), both in the app and in
            // tests using actingAs() (which reuses this exact object rather
            // than a fresh DB fetch).
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
