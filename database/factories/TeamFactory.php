<?php

namespace Database\Factories;

use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        $clubWords = ['AS', 'ASC', 'US', 'Étoile', 'Jaraaf de', 'FC', 'Diaraf'];

        return [
            'competition_id' => Competition::factory(),
            'name' => fake()->randomElement($clubWords).' '.fake()->unique()->city(),
            'primary_color' => fake()->hexColor(),
            'secondary_color' => fake()->hexColor(),
            'city' => fake()->city(),
            'home_ground' => 'Stade '.fake()->city(),
            'manager_name' => fake()->name(),
            'contact_phone' => fake()->numerify('77#######'),
            'contact_email' => fake()->companyEmail(),
        ];
    }
}
