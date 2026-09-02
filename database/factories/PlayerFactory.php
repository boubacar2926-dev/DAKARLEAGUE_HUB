<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            // team_id est rattachée explicitement à competition_id : sinon Team::factory()
            // créait sa propre compétition indépendante, produisant un joueur dont
            // competition_id ne correspondait pas à la compétition réelle de son équipe
            // (incohérence avec la contrainte unique ['competition_id', 'user_id'], RG03).
            'competition_id' => Competition::factory(),
            'team_id' => fn (array $attributes) => Team::factory()->create(['competition_id' => $attributes['competition_id']])->id,
            'first_name' => fake()->firstName('male'),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-38 years', '-16 years'),
            'position' => fake()->randomElement([
                Player::POSITION_GOALKEEPER,
                Player::POSITION_DEFENDER,
                Player::POSITION_MIDFIELDER,
                Player::POSITION_FORWARD,
            ]),
        ];
    }
}
