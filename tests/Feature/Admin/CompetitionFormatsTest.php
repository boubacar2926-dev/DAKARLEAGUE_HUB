<?php

namespace Tests\Feature\Admin;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PublishesMatchLineups;
use Tests\TestCase;

/**
 * Vérifie de bout en bout, via les routes HTTP réelles, que les trois formats de compétition
 * (championnat, poules, élimination directe) sont utilisables par un organisateur.
 */
class CompetitionFormatsTest extends TestCase
{
    use RefreshDatabase;
    use PublishesMatchLineups;

    public function test_organisateur_generates_a_groups_calendar_via_http(): void
    {
        $organisateur = User::factory()->organisateur()->create();
        $competition = Competition::factory()->create([
            'created_by' => $organisateur->id,
            'format' => Competition::FORMAT_GROUPS,
            'number_of_groups' => 2,
        ]);
        Team::factory()->count(8)->create([
            'competition_id' => $competition->id,
            'registration_status' => Team::REGISTRATION_APPROVED,
        ]);

        $response = $this->actingAs($organisateur)->post(route('admin.competitions.matches.generate', $competition));

        $response->assertRedirect(route('admin.competitions.matches.index', $competition));
        $this->assertSame(12, $competition->matches()->count());
        $this->assertEquals(['A', 'B'], $competition->teams()->pluck('group_label')->unique()->sort()->values()->all());
    }

    public function test_organisateur_generates_and_advances_a_knockout_bracket_via_http(): void
    {
        $organisateur = User::factory()->organisateur()->create();
        $competition = Competition::factory()->create([
            'created_by' => $organisateur->id,
            'format' => Competition::FORMAT_KNOCKOUT,
        ]);
        Team::factory()->count(4)->create([
            'competition_id' => $competition->id,
            'registration_status' => Team::REGISTRATION_APPROVED,
        ]);

        $this->actingAs($organisateur)->post(route('admin.competitions.matches.generate', $competition))
            ->assertRedirect(route('admin.competitions.matches.index', $competition));

        $this->assertSame(2, $competition->matches()->where('round', 1)->count());

        // Le tour suivant est refusé tant que le tour 1 n'est pas entièrement joué.
        $this->actingAs($organisateur)
            ->post(route('admin.competitions.matches.generate-next-round', $competition))
            ->assertSessionHas('error');
        $this->assertSame(0, $competition->matches()->where('round', 2)->count());

        foreach ($competition->matches()->where('round', 1)->get() as $match) {
            $this->publishLineups($match, $organisateur);

            $this->actingAs($organisateur)->put(route('admin.matches.result.update', $match), [
                'home_score' => 2,
                'away_score' => 1,
                'events' => [
                    ['team_id' => $match->home_team_id, 'type' => \App\Models\MatchEvent::TYPE_GOAL, 'minute' => 10],
                    ['team_id' => $match->home_team_id, 'type' => \App\Models\MatchEvent::TYPE_GOAL, 'minute' => 30],
                    ['team_id' => $match->away_team_id, 'type' => \App\Models\MatchEvent::TYPE_GOAL, 'minute' => 60],
                ],
            ])->assertSessionDoesntHaveErrors();
        }

        $this->actingAs($organisateur)
            ->post(route('admin.competitions.matches.generate-next-round', $competition))
            ->assertRedirect(route('admin.competitions.matches.index', $competition));

        $this->assertSame(1, $competition->matches()->where('round', 2)->count());
    }
}
