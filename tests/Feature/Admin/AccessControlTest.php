<?php

namespace Tests\Feature\Admin;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PublishesMatchLineups;
use Tests\TestCase;

/**
 * Vérifie la matrice des droits du cahier des charges (§2.3) : chaque rôle ne doit accéder
 * qu'aux fonctions et compétitions qui lui sont autorisées (RG12).
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;
    use PublishesMatchLineups;

    public function test_guest_is_redirected_to_login_for_the_admin_area(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_joueur_role_cannot_access_the_admin_area(): void
    {
        $joueur = User::factory()->create(['role' => User::ROLE_JOUEUR]);

        $this->actingAs($joueur)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_organisateur_can_create_a_competition(): void
    {
        $organisateur = User::factory()->organisateur()->create();

        $response = $this->actingAs($organisateur)->post(route('admin.competitions.store'), [
            'name' => 'Ligue de Dakar',
            'season' => '2026',
            'format' => Competition::FORMAT_SINGLE_ROUND,
            'status' => Competition::STATUS_DRAFT,
            'points_win' => 3,
            'points_draw' => 1,
            'points_loss' => 0,
            'yellow_card_suspension_threshold' => 3,
            'red_card_suspension_matches' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('competitions', ['name' => 'Ligue de Dakar', 'created_by' => $organisateur->id]);
    }

    public function test_responsable_role_cannot_create_a_competition(): void
    {
        $responsable = User::factory()->responsable()->create();

        $this->actingAs($responsable)->get(route('admin.competitions.create'))->assertForbidden();
    }

    public function test_organisateur_cannot_manage_a_competition_they_do_not_organize(): void
    {
        $owner = User::factory()->organisateur()->create();
        $intruder = User::factory()->organisateur()->create();
        $competition = Competition::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($intruder)->get(route('admin.competitions.edit', $competition))->assertForbidden();
        $this->actingAs($intruder)->delete(route('admin.competitions.destroy', $competition))->assertForbidden();
        $this->actingAs($intruder)->get(route('admin.competitions.teams.create', $competition))->assertForbidden();
        $this->actingAs($intruder)->get(route('admin.competitions.matches.create', $competition))->assertForbidden();

        $this->assertDatabaseHas('competitions', ['id' => $competition->id]);
    }

    public function test_responsable_cannot_manage_players_when_competition_disallows_it(): void
    {
        $responsable = User::factory()->responsable()->create();
        $competition = Competition::factory()->create(['allow_team_managers_to_manage_players' => false]);
        $team = Team::factory()->create([
            'competition_id' => $competition->id,
            'manager_user_id' => $responsable->id,
        ]);

        $this->actingAs($responsable)->get(route('admin.teams.players.create', $team))->assertForbidden();
    }

    public function test_responsable_can_manage_players_when_competition_allows_it(): void
    {
        $responsable = User::factory()->responsable()->create();
        $competition = Competition::factory()->create(['allow_team_managers_to_manage_players' => true]);
        $team = Team::factory()->create([
            'competition_id' => $competition->id,
            'manager_user_id' => $responsable->id,
        ]);

        $response = $this->actingAs($responsable)->post(route('admin.teams.players.store', $team), [
            'first_name' => 'Sadio',
            'last_name' => 'Mane',
            'position' => Player::POSITION_FORWARD,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('players', ['first_name' => 'Sadio', 'team_id' => $team->id]);
    }

    public function test_responsable_of_another_team_cannot_manage_players_even_with_permission_enabled(): void
    {
        $responsable = User::factory()->responsable()->create();
        $someoneElse = User::factory()->responsable()->create();
        $competition = Competition::factory()->create(['allow_team_managers_to_manage_players' => true]);
        $team = Team::factory()->create([
            'competition_id' => $competition->id,
            'manager_user_id' => $someoneElse->id,
        ]);

        $this->actingAs($responsable)->get(route('admin.teams.players.create', $team))->assertForbidden();
    }

    public function test_activity_log_is_scoped_to_the_organisateurs_own_competitions(): void
    {
        $organisateur = User::factory()->organisateur()->create();
        $ownCompetition = Competition::factory()->create(['created_by' => $organisateur->id]);
        $otherCompetition = Competition::factory()->create();

        \App\Models\ActivityLog::record('competition.created', $ownCompetition, 'Test', competitionId: $ownCompetition->id);
        \App\Models\ActivityLog::record('competition.created', $otherCompetition, 'Test', competitionId: $otherCompetition->id);

        $response = $this->actingAs($organisateur)->get(route('admin.activity-logs.index'));

        $response->assertOk();
        $logs = $response->viewData('logs')->getCollection();
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($log) => $log->competition_id === $ownCompetition->id));
    }

    public function test_super_admin_sees_activity_logs_for_every_competition(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $competitionA = Competition::factory()->create();
        $competitionB = Competition::factory()->create();

        \App\Models\ActivityLog::record('competition.created', $competitionA, 'Test', competitionId: $competitionA->id);
        \App\Models\ActivityLog::record('competition.created', $competitionB, 'Test', competitionId: $competitionB->id);

        $response = $this->actingAs($superAdmin)->get(route('admin.activity-logs.index'));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('logs')->total());
    }

    public function test_team_name_must_be_unique_within_the_same_competition(): void
    {
        $organisateur = User::factory()->organisateur()->create();
        $competition = Competition::factory()->create(['created_by' => $organisateur->id]);
        Team::factory()->create(['competition_id' => $competition->id, 'name' => 'ASC Jeanne d\'Arc']);

        $response = $this->actingAs($organisateur)->post(route('admin.competitions.teams.store', $competition), [
            'name' => 'ASC Jeanne d\'Arc',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_match_result_is_rejected_when_goals_do_not_match_the_declared_score(): void
    {
        $organisateur = User::factory()->organisateur()->create();
        $competition = Competition::factory()->create(['created_by' => $organisateur->id]);
        $home = Team::factory()->create(['competition_id' => $competition->id]);
        $away = Team::factory()->create(['competition_id' => $competition->id]);
        $match = GameMatch::factory()->create([
            'competition_id' => $competition->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        $response = $this->actingAs($organisateur)->put(route('admin.matches.result.update', $match), [
            'home_score' => 2,
            'away_score' => 0,
            'events' => [
                ['team_id' => $home->id, 'type' => \App\Models\MatchEvent::TYPE_GOAL, 'minute' => 10],
            ],
        ]);

        $response->assertSessionHasErrors('home_score');
        $this->assertDatabaseMissing('matches', ['id' => $match->id, 'status' => GameMatch::STATUS_FINISHED]);
    }

    public function test_knockout_match_ending_in_a_draw_requires_a_penalty_shootout_winner(): void
    {
        $organisateur = User::factory()->organisateur()->create();
        $competition = Competition::factory()->create([
            'created_by' => $organisateur->id,
            'format' => Competition::FORMAT_KNOCKOUT,
        ]);
        $home = Team::factory()->create(['competition_id' => $competition->id]);
        $away = Team::factory()->create(['competition_id' => $competition->id]);
        $match = GameMatch::factory()->create([
            'competition_id' => $competition->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        $coherentEvents = [
            ['team_id' => $home->id, 'type' => \App\Models\MatchEvent::TYPE_GOAL, 'minute' => 10],
            ['team_id' => $away->id, 'type' => \App\Models\MatchEvent::TYPE_GOAL, 'minute' => 20],
        ];

        // Score nul sans tirs au but : refusé, un tableau à élimination directe a besoin d'un vainqueur.
        $response = $this->actingAs($organisateur)->put(route('admin.matches.result.update', $match), [
            'home_score' => 1,
            'away_score' => 1,
            'events' => $coherentEvents,
        ]);
        $response->assertSessionHasErrors('home_penalties');
        $this->assertDatabaseMissing('matches', ['id' => $match->id, 'status' => GameMatch::STATUS_FINISHED]);

        // Avec un résultat de tirs au but décisif, la validation passe.
        $this->publishLineups($match, $organisateur);

        $response = $this->actingAs($organisateur)->put(route('admin.matches.result.update', $match), [
            'home_score' => 1,
            'away_score' => 1,
            'home_penalties' => 5,
            'away_penalties' => 4,
            'events' => $coherentEvents,
        ]);
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('matches', [
            'id' => $match->id,
            'status' => GameMatch::STATUS_FINISHED,
            'home_penalties' => 5,
            'away_penalties' => 4,
        ]);
    }

    public function test_creating_a_groups_competition_requires_the_number_of_groups(): void
    {
        $organisateur = User::factory()->organisateur()->create();

        $response = $this->actingAs($organisateur)->post(route('admin.competitions.store'), [
            'name' => 'Coupe des Poules',
            'season' => '2026',
            'format' => Competition::FORMAT_GROUPS,
            'status' => Competition::STATUS_DRAFT,
            'points_win' => 3,
            'points_draw' => 1,
            'points_loss' => 0,
            'yellow_card_suspension_threshold' => 3,
            'red_card_suspension_matches' => 1,
        ]);

        $response->assertSessionHasErrors('number_of_groups');
    }
}
