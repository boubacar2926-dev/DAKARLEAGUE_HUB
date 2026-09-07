<?php

namespace Tests\Feature\Admin;

use App\Models\Competition;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le poste d'un joueur est désormais obligatoire (nécessaire pour vérifier qu'une composition
 * aligne un gardien), et l'effectif d'une équipe est plafonné (Player::MAX_ROSTER_SIZE).
 */
class PlayerRosterTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeam(): Team
    {
        $organisateur = User::factory()->organisateur()->create();
        $competition = Competition::factory()->create(['created_by' => $organisateur->id]);

        return Team::factory()->create(['competition_id' => $competition->id]);
    }

    public function test_player_position_is_required(): void
    {
        $team = $this->makeTeam();
        $organisateur = User::find($team->competition->created_by);

        $response = $this->actingAs($organisateur)->post(route('admin.teams.players.store', $team), [
            'first_name' => 'Amadou',
            'last_name' => 'Diallo',
        ]);

        $response->assertSessionHasErrors('position');
        $this->assertDatabaseMissing('players', ['first_name' => 'Amadou', 'last_name' => 'Diallo']);
    }

    public function test_team_roster_cannot_exceed_the_maximum_size(): void
    {
        $team = $this->makeTeam();
        $organisateur = User::find($team->competition->created_by);

        Player::factory()->count(Player::MAX_ROSTER_SIZE)->create([
            'team_id' => $team->id,
            'competition_id' => $team->competition_id,
        ]);

        $response = $this->actingAs($organisateur)->post(route('admin.teams.players.store', $team), [
            'first_name' => 'Ousmane',
            'last_name' => 'Fall',
            'position' => Player::POSITION_FORWARD,
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('players', ['first_name' => 'Ousmane', 'last_name' => 'Fall']);
        $this->assertSame(Player::MAX_ROSTER_SIZE, $team->players()->count());
    }

    public function test_editing_an_existing_player_is_not_blocked_by_the_roster_cap(): void
    {
        $team = $this->makeTeam();
        $organisateur = User::find($team->competition->created_by);

        Player::factory()->count(Player::MAX_ROSTER_SIZE)->create([
            'team_id' => $team->id,
            'competition_id' => $team->competition_id,
        ]);
        $player = $team->players()->first();

        $response = $this->actingAs($organisateur)->put(route('admin.players.update', $player), [
            'first_name' => 'Modifié',
            'last_name' => $player->last_name,
            'position' => $player->position,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('players', ['id' => $player->id, 'first_name' => 'Modifié']);
    }
}
