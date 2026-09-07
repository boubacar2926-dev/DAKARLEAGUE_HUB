<?php

namespace Tests\Unit\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\MatchEvent;
use App\Models\Player;
use App\Models\Team;
use App\Services\SuspensionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspensionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SuspensionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SuspensionService;
    }

    private function competition(array $overrides = []): Competition
    {
        return Competition::factory()->create(array_merge([
            'yellow_card_suspension_threshold' => 3,
            'red_card_suspension_matches' => 1,
        ], $overrides));
    }

    private function playedMatch(Competition $competition, Team $home, Team $away, int $round): GameMatch
    {
        return GameMatch::factory()->finished(1, 0)->create([
            'competition_id' => $competition->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'round' => $round,
        ]);
    }

    public function test_player_with_no_prior_matches_is_not_suspended(): void
    {
        $competition = $this->competition();
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $opponent = Team::factory()->create(['competition_id' => $competition->id]);
        $player = Player::factory()->create(['team_id' => $team->id, 'competition_id' => $competition->id]);
        $match = $this->playedMatch($competition, $team, $opponent, 1);

        $this->assertFalse($this->service->isSuspended($player, $match));
    }

    public function test_direct_red_card_suspends_the_next_match(): void
    {
        $competition = $this->competition(['red_card_suspension_matches' => 1]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $opponent = Team::factory()->create(['competition_id' => $competition->id]);
        $player = Player::factory()->create(['team_id' => $team->id, 'competition_id' => $competition->id]);

        $match1 = $this->playedMatch($competition, $team, $opponent, 1);
        MatchEvent::create([
            'match_id' => $match1->id,
            'team_id' => $team->id,
            'player_id' => $player->id,
            'type' => MatchEvent::TYPE_RED_CARD,
            'minute' => 40,
        ]);

        $match2 = $this->playedMatch($competition, $team, $opponent, 2);

        $status = $this->service->statusForPlayer($player, $match2);
        $this->assertTrue($status['suspended']);
        $this->assertStringContainsString('rouge', $status['reason']);
    }

    public function test_two_yellow_cards_in_same_match_suspends_like_a_red_card(): void
    {
        $competition = $this->competition();
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $opponent = Team::factory()->create(['competition_id' => $competition->id]);
        $player = Player::factory()->create(['team_id' => $team->id, 'competition_id' => $competition->id]);

        $match1 = $this->playedMatch($competition, $team, $opponent, 1);
        foreach (range(1, 2) as $i) {
            MatchEvent::create([
                'match_id' => $match1->id,
                'team_id' => $team->id,
                'player_id' => $player->id,
                'type' => MatchEvent::TYPE_YELLOW_CARD,
                'minute' => 20 * $i,
            ]);
        }

        $match2 = $this->playedMatch($competition, $team, $opponent, 2);

        $this->assertTrue($this->service->isSuspended($player, $match2));
    }

    public function test_suspension_is_lifted_after_serving_it(): void
    {
        $competition = $this->competition(['red_card_suspension_matches' => 1]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $opponent = Team::factory()->create(['competition_id' => $competition->id]);
        $player = Player::factory()->create(['team_id' => $team->id, 'competition_id' => $competition->id]);

        $match1 = $this->playedMatch($competition, $team, $opponent, 1);
        MatchEvent::create([
            'match_id' => $match1->id,
            'team_id' => $team->id,
            'player_id' => $player->id,
            'type' => MatchEvent::TYPE_RED_CARD,
            'minute' => 40,
        ]);

        $match2 = $this->playedMatch($competition, $team, $opponent, 2); // suspension served here
        $match3 = $this->playedMatch($competition, $team, $opponent, 3);

        $this->assertTrue($this->service->isSuspended($player, $match2));
        $this->assertFalse($this->service->isSuspended($player, $match3));
    }

    public function test_accumulated_yellow_cards_trigger_a_one_match_suspension_then_reset(): void
    {
        $competition = $this->competition(['yellow_card_suspension_threshold' => 3]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $opponent = Team::factory()->create(['competition_id' => $competition->id]);
        $player = Player::factory()->create(['team_id' => $team->id, 'competition_id' => $competition->id]);

        // One yellow per match for 3 matches reaches the threshold.
        for ($round = 1; $round <= 3; $round++) {
            $match = $this->playedMatch($competition, $team, $opponent, $round);
            MatchEvent::create([
                'match_id' => $match->id,
                'team_id' => $team->id,
                'player_id' => $player->id,
                'type' => MatchEvent::TYPE_YELLOW_CARD,
                'minute' => 30,
            ]);
        }

        $match4 = $this->playedMatch($competition, $team, $opponent, 4); // suspension served
        $match5 = $this->playedMatch($competition, $team, $opponent, 5); // counter reset

        $status4 = $this->service->statusForPlayer($player, $match4);
        $this->assertTrue($status4['suspended']);
        $this->assertStringContainsString('cumul', $status4['reason']);
        $this->assertFalse($this->service->isSuspended($player, $match5));
    }

    public function test_yellow_card_accumulation_resets_between_knockout_rounds(): void
    {
        $competition = $this->competition([
            'format' => Competition::FORMAT_KNOCKOUT,
            'yellow_card_suspension_threshold' => 2,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $opponent = Team::factory()->create(['competition_id' => $competition->id]);
        $player = Player::factory()->create(['team_id' => $team->id, 'competition_id' => $competition->id]);

        // Un jaune au tour 1, un jaune au tour 2 : sans remise à zéro entre les tours, le seuil
        // de 2 serait atteint et suspendrait le tour 3.
        $match1 = $this->playedMatch($competition, $team, $opponent, 1);
        MatchEvent::create([
            'match_id' => $match1->id,
            'team_id' => $team->id,
            'player_id' => $player->id,
            'type' => MatchEvent::TYPE_YELLOW_CARD,
            'minute' => 30,
        ]);

        $match2 = $this->playedMatch($competition, $team, $opponent, 2);
        MatchEvent::create([
            'match_id' => $match2->id,
            'team_id' => $team->id,
            'player_id' => $player->id,
            'type' => MatchEvent::TYPE_YELLOW_CARD,
            'minute' => 30,
        ]);

        $match3 = $this->playedMatch($competition, $team, $opponent, 3);

        $this->assertFalse($this->service->isSuspended($player, $match3));
    }

    public function test_yellow_card_accumulation_still_works_within_the_same_knockout_round(): void
    {
        // Deux matchs disputés au sein d'un même tour (ex. rencontre à rejouer) : le seuil doit
        // toujours se déclencher normalement ici — seul un changement de tour remet le compteur
        // à zéro, pas chaque match.
        $competition = $this->competition([
            'format' => Competition::FORMAT_KNOCKOUT,
            'yellow_card_suspension_threshold' => 2,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $opponent = Team::factory()->create(['competition_id' => $competition->id]);
        $player = Player::factory()->create(['team_id' => $team->id, 'competition_id' => $competition->id]);

        $match1 = $this->playedMatch($competition, $team, $opponent, 1);
        MatchEvent::create([
            'match_id' => $match1->id,
            'team_id' => $team->id,
            'player_id' => $player->id,
            'type' => MatchEvent::TYPE_YELLOW_CARD,
            'minute' => 20,
        ]);

        $match1Replay = $this->playedMatch($competition, $team, $opponent, 1);
        MatchEvent::create([
            'match_id' => $match1Replay->id,
            'team_id' => $team->id,
            'player_id' => $player->id,
            'type' => MatchEvent::TYPE_YELLOW_CARD,
            'minute' => 30,
        ]);

        $match2 = $this->playedMatch($competition, $team, $opponent, 2);

        $this->assertTrue($this->service->isSuspended($player, $match2));
    }

    public function test_suspended_player_ids_for_match_covers_both_teams(): void
    {
        $competition = $this->competition();
        $home = Team::factory()->create(['competition_id' => $competition->id]);
        $away = Team::factory()->create(['competition_id' => $competition->id]);
        $homePlayer = Player::factory()->create(['team_id' => $home->id, 'competition_id' => $competition->id]);
        $awayPlayer = Player::factory()->create(['team_id' => $away->id, 'competition_id' => $competition->id]);

        $match1 = $this->playedMatch($competition, $home, $away, 1);
        MatchEvent::create([
            'match_id' => $match1->id,
            'team_id' => $away->id,
            'player_id' => $awayPlayer->id,
            'type' => MatchEvent::TYPE_RED_CARD,
            'minute' => 10,
        ]);

        $match2 = $this->playedMatch($competition, $home, $away, 2);

        $suspended = $this->service->suspendedPlayerIdsForMatch($match2);

        $this->assertTrue($suspended->has($awayPlayer->id));
        $this->assertFalse($suspended->has($homePlayer->id));
    }
}
