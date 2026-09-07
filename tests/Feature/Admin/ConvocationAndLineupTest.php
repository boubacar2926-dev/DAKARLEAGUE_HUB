<?php

namespace Tests\Feature\Admin;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Lineup;
use App\Models\MatchEvent;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PublishesMatchLineups;
use Tests\TestCase;

/**
 * Un résultat ne peut être saisi qu'une fois la convocation (18 joueurs min. par équipe) puis
 * la composition (exactement 11 titulaires, le reste remplaçant) publiées pour les deux équipes.
 */
class ConvocationAndLineupTest extends TestCase
{
    use RefreshDatabase;
    use PublishesMatchLineups;

    private function makeMatch(): GameMatch
    {
        $organisateur = User::factory()->organisateur()->create();
        $competition = Competition::factory()->create(['created_by' => $organisateur->id]);
        $home = Team::factory()->create(['competition_id' => $competition->id, 'registration_status' => Team::REGISTRATION_APPROVED]);
        $away = Team::factory()->create(['competition_id' => $competition->id, 'registration_status' => Team::REGISTRATION_APPROVED]);

        $match = GameMatch::factory()->create([
            'competition_id' => $competition->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        return $match->fresh(['homeTeam', 'awayTeam']);
    }

    public function test_convocation_is_rejected_with_fewer_than_eighteen_players(): void
    {
        $match = $this->makeMatch();
        $organisateur = User::find($match->competition->created_by);

        $players = Player::factory()->count(17)->create(['team_id' => $match->home_team_id, 'competition_id' => $match->competition_id]);

        $response = $this->actingAs($organisateur)->put(route('admin.matches.convocation.update', $match), [
            'home' => $players->pluck('id')->all(),
            'away' => [],
        ]);

        $response->assertSessionHasErrors('home');
        $this->assertSame(0, Lineup::where('match_id', $match->id)->count());
    }

    public function test_composition_page_redirects_to_convocation_when_not_yet_published(): void
    {
        $match = $this->makeMatch();
        $organisateur = User::find($match->competition->created_by);

        $response = $this->actingAs($organisateur)->get(route('admin.matches.lineup.edit', $match));

        $response->assertRedirect(route('admin.matches.convocation.edit', $match));
    }

    public function test_composition_requires_exactly_eleven_starters(): void
    {
        $match = $this->makeMatch();
        $organisateur = User::find($match->competition->created_by);

        $homePlayers = Player::factory()->count(18)->create(['team_id' => $match->home_team_id, 'competition_id' => $match->competition_id]);
        $awayPlayers = Player::factory()->count(18)->create(['team_id' => $match->away_team_id, 'competition_id' => $match->competition_id]);

        $this->actingAs($organisateur)->put(route('admin.matches.convocation.update', $match), [
            'home' => $homePlayers->pluck('id')->all(),
            'away' => $awayPlayers->pluck('id')->all(),
        ])->assertSessionDoesntHaveErrors();

        // Seulement 10 titulaires déclarés : refusé.
        $selections = $homePlayers->take(10)->mapWithKeys(fn (Player $p) => [$p->id => 'titulaire'])
            ->union($homePlayers->skip(10)->mapWithKeys(fn (Player $p) => [$p->id => 'remplacant']))
            ->all();

        $response = $this->actingAs($organisateur)->put(route('admin.matches.lineup.update', $match), [
            'home' => $selections,
            'away' => $awayPlayers->take(11)->mapWithKeys(fn (Player $p) => [$p->id => 'titulaire'])
                ->union($awayPlayers->skip(11)->mapWithKeys(fn (Player $p) => [$p->id => 'remplacant']))
                ->all(),
        ]);

        $response->assertSessionHasErrors('home');
        $this->assertSame(0, Lineup::where('match_id', $match->id)->where('is_starting', true)->count());
    }

    public function test_composition_is_rejected_without_a_goalkeeper_among_starters(): void
    {
        $match = $this->makeMatch();
        $organisateur = User::find($match->competition->created_by);

        // 18 joueurs, aucun gardien.
        $homePlayers = Player::factory()->count(18)->create([
            'team_id' => $match->home_team_id,
            'competition_id' => $match->competition_id,
            'position' => Player::POSITION_FORWARD,
        ]);
        $awayPlayers = Player::factory()->count(18)->create(['team_id' => $match->away_team_id, 'competition_id' => $match->competition_id]);

        $this->actingAs($organisateur)->put(route('admin.matches.convocation.update', $match), [
            'home' => $homePlayers->pluck('id')->all(),
            'away' => $awayPlayers->pluck('id')->all(),
        ])->assertSessionDoesntHaveErrors();

        $selections = $homePlayers->take(11)->mapWithKeys(fn (Player $p) => [$p->id => 'titulaire'])
            ->union($homePlayers->skip(11)->mapWithKeys(fn (Player $p) => [$p->id => 'remplacant']))
            ->all();

        $response = $this->actingAs($organisateur)->put(route('admin.matches.lineup.update', $match), [
            'home' => $selections,
            'away' => $this->startersAndSubsForTest($awayPlayers),
        ]);

        $response->assertSessionHasErrors('home');
        $this->assertSame(0, Lineup::where('match_id', $match->id)->where('team_id', $match->home_team_id)->where('is_starting', true)->count());
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Player>  $players
     */
    private function startersAndSubsForTest($players): array
    {
        return $players->take(11)->mapWithKeys(fn (Player $p) => [$p->id => 'titulaire'])
            ->union($players->skip(11)->mapWithKeys(fn (Player $p) => [$p->id => 'remplacant']))
            ->all();
    }

    public function test_result_cannot_be_entered_before_convocation_and_composition_are_published(): void
    {
        $match = $this->makeMatch();
        $organisateur = User::find($match->competition->created_by);

        $response = $this->actingAs($organisateur)->put(route('admin.matches.result.update', $match), [
            'home_score' => 1,
            'away_score' => 0,
            'events' => [
                ['team_id' => $match->home_team_id, 'type' => MatchEvent::TYPE_GOAL, 'minute' => 10],
            ],
        ]);

        $response->assertRedirect(route('admin.competitions.matches.index', $match->competition_id));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('matches', ['id' => $match->id, 'status' => GameMatch::STATUS_FINISHED]);
    }

    public function test_result_can_be_entered_once_convocation_and_composition_are_complete(): void
    {
        $match = $this->makeMatch();
        $organisateur = User::find($match->competition->created_by);

        $this->publishLineups($match, $organisateur);

        $this->actingAs($organisateur)->get(route('admin.matches.lineup.edit', $match))->assertOk();
        $this->actingAs($organisateur)->get(route('admin.matches.convocation.edit', $match))->assertOk();

        $response = $this->actingAs($organisateur)->put(route('admin.matches.result.update', $match), [
            'home_score' => 1,
            'away_score' => 0,
            'events' => [
                ['team_id' => $match->home_team_id, 'type' => MatchEvent::TYPE_GOAL, 'minute' => 10],
            ],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('matches', ['id' => $match->id, 'status' => GameMatch::STATUS_FINISHED]);
    }

    public function test_goal_is_rejected_for_a_player_who_was_not_convoked_for_this_match(): void
    {
        $match = $this->makeMatch();
        $organisateur = User::find($match->competition->created_by);

        $this->publishLineups($match, $organisateur);

        // Joueur bien réel de l'effectif domicile, mais jamais inclus dans la convocation.
        $outsider = Player::factory()->create(['team_id' => $match->home_team_id, 'competition_id' => $match->competition_id]);

        $response = $this->actingAs($organisateur)->put(route('admin.matches.result.update', $match), [
            'home_score' => 1,
            'away_score' => 0,
            'events' => [
                ['team_id' => $match->home_team_id, 'player_id' => $outsider->id, 'type' => MatchEvent::TYPE_GOAL, 'minute' => 10],
            ],
        ]);

        $response->assertSessionHasErrors('events.0.player_id');
        $this->assertDatabaseMissing('matches', ['id' => $match->id, 'status' => GameMatch::STATUS_FINISHED]);
    }

    public function test_goal_is_accepted_for_a_player_who_was_convoked_for_this_match(): void
    {
        $match = $this->makeMatch();
        $organisateur = User::find($match->competition->created_by);

        $this->publishLineups($match, $organisateur);

        $this->actingAs($organisateur)->get(route('admin.matches.result.edit', $match))->assertOk();

        $scorer = Lineup::where('match_id', $match->id)->where('team_id', $match->home_team_id)->first()->player_id;

        $response = $this->actingAs($organisateur)->put(route('admin.matches.result.update', $match), [
            'home_score' => 1,
            'away_score' => 0,
            'events' => [
                ['team_id' => $match->home_team_id, 'player_id' => $scorer, 'type' => MatchEvent::TYPE_GOAL, 'minute' => 10],
            ],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('matches', ['id' => $match->id, 'status' => GameMatch::STATUS_FINISHED]);
    }

    public function test_goal_scorer_check_does_not_block_legacy_matches_without_any_composition(): void
    {
        // Rétrocompatibilité : un match sans aucune ligne de composition (données antérieures à
        // cette règle) ne doit pas empêcher la correction de son résultat.
        $match = $this->makeMatch();
        $organisateur = User::find($match->competition->created_by);
        $player = Player::factory()->create(['team_id' => $match->home_team_id, 'competition_id' => $match->competition_id]);

        $match->update(['status' => GameMatch::STATUS_FINISHED, 'home_score' => 1, 'away_score' => 0]);

        $response = $this->actingAs($organisateur)->put(route('admin.matches.result.update', $match), [
            'home_score' => 1,
            'away_score' => 0,
            'events' => [
                ['team_id' => $match->home_team_id, 'player_id' => $player->id, 'type' => MatchEvent::TYPE_GOAL, 'minute' => 10],
            ],
        ]);

        $response->assertSessionDoesntHaveErrors();
    }

    public function test_republishing_the_convocation_clears_the_previous_composition(): void
    {
        $match = $this->makeMatch();
        $organisateur = User::find($match->competition->created_by);

        $this->publishLineups($match, $organisateur);
        $this->assertSame(11, Lineup::where('match_id', $match->id)->where('team_id', $match->home_team_id)->where('is_starting', true)->count());

        $newHomePlayers = Player::factory()->count(18)->create(['team_id' => $match->home_team_id, 'competition_id' => $match->competition_id]);
        $existingAway = Lineup::where('match_id', $match->id)->where('team_id', $match->away_team_id)->pluck('player_id');

        $this->actingAs($organisateur)->put(route('admin.matches.convocation.update', $match), [
            'home' => $newHomePlayers->pluck('id')->all(),
            'away' => $existingAway->all(),
        ])->assertSessionDoesntHaveErrors();

        // La composition précédente (anciens joueurs domicile) doit avoir été effacée.
        $this->assertSame(0, Lineup::where('match_id', $match->id)->where('team_id', $match->home_team_id)->where('is_starting', true)->count());
    }
}
