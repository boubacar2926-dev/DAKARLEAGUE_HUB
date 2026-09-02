<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Championnat '.fake()->city(),
            'season' => (string) fake()->numberBetween(2024, 2026),
            'category' => fake()->randomElement(['Senior', 'U20', 'Vétérans']),
            'description' => fake()->paragraph(),
            'start_date' => fake()->dateTimeBetween('-2 months', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+2 months', '+6 months'),
            'format' => Competition::FORMAT_DOUBLE_ROUND,
            'status' => Competition::STATUS_IN_PROGRESS,
            'points_win' => 3,
            'points_draw' => 1,
            'points_loss' => 0,
            'created_by' => User::factory()->organisateur(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Competition::STATUS_DRAFT,
        ]);
    }
}
