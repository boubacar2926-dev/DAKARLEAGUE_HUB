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
            'team_id' => Team::factory(),
            'competition_id' => Competition::factory(),
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
