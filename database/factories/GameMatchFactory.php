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
            // home_team_id / away_team_id sont rattachées explicitement à la même compétition
            // que le match : sans cela, Team::factory() créait chacune sa propre compétition
            // via sa propre factory imbriquée, produisant un match dont les deux équipes
            // n'appartiennent ni à la compétition du match ni à la même compétition entre elles.
            'competition_id' => Competition::factory(),
            'home_team_id' => fn (array $attributes) => Team::factory()->create(['competition_id' => $attributes['competition_id']])->id,
            'away_team_id' => fn (array $attributes) => Team::factory()->create(['competition_id' => $attributes['competition_id']])->id,
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
