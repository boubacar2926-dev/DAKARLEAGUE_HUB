<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMatch>
 */
class GameMatchFactory extends Factory
{
    protected $model = GameMatch::class;

    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'round' => fake()->numberBetween(1, 10),
            'scheduled_at' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'venue' => 'Stade '.fake()->city(),
            'status' => GameMatch::STATUS_SCHEDULED,
        ];
    }

    public function finished(int $homeScore, int $awayScore): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GameMatch::STATUS_FINISHED,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'validated_at' => now(),
        ]);
    }
}
