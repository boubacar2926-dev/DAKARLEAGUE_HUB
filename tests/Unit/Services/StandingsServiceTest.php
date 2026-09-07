<?php

namespace Tests\Unit\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\MatchEvent;
use App\Models\Team;
use App\Services\StandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private StandingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StandingsService;
    }

    private function competition(array $overrides = []): Competition
    {
        return Competition::factory()->create(array_merge([
            'points_win' => 3,
            'points_draw' => 1,
            'points_loss' => 0,
        ], $overrides));
    }

    private function team(Competition $competition, string $name): Team
    {
        return Team::factory()->create([
            'competition_id' => $competition->id,
            'name' => $name,
            'registration_status' => Team::REGISTRATION_APPROVED,
        ]);
    }

    private function finishedMatch(Competition $competition, Team $home, Team $away, int $homeScore, int $awayScore): GameMatch
    {
        return GameMatch::factory()->finished($homeScore, $awayScore)->create([
            'competition_id' => $competition->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);
    }

    public function test_ranks_teams_by_points_first(): void
    {
        $competition = $this->competition();
        $a = $this->team($competition, 'A');
        $b = $this->team($competition, 'B');
        $c = $this->team($competition, 'C');

        $this->finishedMatch($competition, $a, $b, 2, 0); // A win, B loss
        $this->finishedMatch($competition, $c, $b, 1, 1); // draw

        $standings = $this->service->calculate($competition);

        $this->assertSame(['A', 'C', 'B'], $standings->pluck('team.name')->all());
        $this->assertSame([1, 2, 3], $standings->pluck('rank')->all());
    }

    public function test_breaks_points_tie_with_goal_difference(): void
    {
        $competition = $this->competition();
        $a = $this->team($competition, 'A');
        $b = $this->team($competition, 'B');
        $c = $this->team($competition, 'C');
        $d = $this->team($competition, 'D');

        // A: 3 pts, GD +3. C: 3 pts, GD +1. Both won their only match, only GD differs.
        $this->finishedMatch($competition, $a, $b, 3, 0);
        $this->finishedMatch($competition, $c, $d, 1, 0);

        $standings = $this->service->calculate($competition)->keyBy('team.name');

        $this->assertSame(3, $standings['A']['goal_difference']);
        $this->assertSame(1, $standings['C']['goal_difference']);
        $this->assertTrue($standings['A']['rank'] < $standings['C']['rank']);
    }

    public function test_breaks_points_and_goal_difference_tie_with_goals_scored(): void
    {
        $competition = $this->competition();
        $e = $this->team($competition, 'E');
        $f = $this->team($competition, 'F');
        $g = $this->team($competition, 'G');
        $h = $this->team($competition, 'H');

        // E: 3 pts, GD +2, GF 2. G: 3 pts, GD +2, GF 3 -> G must rank above E.
        $this->finishedMatch($competition, $e, $f, 2, 0);
        $this->finishedMatch($competition, $g, $h, 3, 1);

        $standings = $this->service->calculate($competition)->keyBy('team.name');

        $this->assertSame(3, $standings['E']['points']);
        $this->assertSame(3, $standings['G']['points']);
        $this->assertSame($standings['E']['goal_difference'], $standings['G']['goal_difference']);
        $this->assertTrue($standings['G']['rank'] < $standings['E']['rank']);
    }

    public function test_breaks_remaining_tie_with_fair_play(): void
    {
        $competition = $this->competition();
        $i = $this->team($competition, 'I');
        $j = $this->team($competition, 'J');
        $k = $this->team($competition, 'K');
        $l = $this->team($competition, 'L');

        // I and J end up perfectly tied on points/GD/GF; only cards differ.
        $matchI = $this->finishedMatch($competition, $i, $k, 1, 0);
        $matchJ = $this->finishedMatch($competition, $j, $l, 1, 0);

        MatchEvent::create([
            'match_id' => $matchJ->id,
            'team_id' => $j->id,
            'type' => MatchEvent::TYPE_YELLOW_CARD,
            'minute' => 60,
        ]);

        $standings = $this->service->calculate($competition)->keyBy('team.name');

        $this->assertSame($standings['I']['points'], $standings['J']['points']);
        $this->assertSame($standings['I']['goal_difference'], $standings['J']['goal_difference']);
        $this->assertSame($standings['I']['goals_for'], $standings['J']['goals_for']);
        $this->assertSame(0, $standings['I']['yellow_cards']);
        $this->assertSame(1, $standings['J']['yellow_cards']);
        $this->assertTrue($standings['I']['rank'] < $standings['J']['rank'], 'Fewer cards must rank higher once points/GD/GF are tied.');
    }

    public function test_breaks_final_tie_with_head_to_head(): void
    {
        $competition = $this->competition();
        $m = $this->team($competition, 'M');
        $n = $this->team($competition, 'N');
        $x = $this->team($competition, 'X');
        $y = $this->team($competition, 'Y');

        // M and N end up tied on points/GD/GF/fair-play, but M beat N head-to-head.
        $this->finishedMatch($competition, $m, $n, 1, 0); // M beats N directly
        $this->finishedMatch($competition, $n, $x, 1, 0); // N's compensating win
        $this->finishedMatch($competition, $y, $m, 1, 0); // M's compensating loss

        $standings = $this->service->calculate($competition)->keyBy('team.name');

        $this->assertSame($standings['M']['points'], $standings['N']['points']);
        $this->assertSame($standings['M']['goal_difference'], $standings['N']['goal_difference']);
        $this->assertSame($standings['M']['goals_for'], $standings['N']['goals_for']);
        $this->assertTrue($standings['M']['rank'] < $standings['N']['rank'], 'M must rank above N: it won their direct confrontation.');
    }

    public function test_ignores_matches_that_are_not_finished_or_have_no_score(): void
    {
        $competition = $this->competition();
        $a = $this->team($competition, 'A');
        $b = $this->team($competition, 'B');

        GameMatch::factory()->create([
            'competition_id' => $competition->id,
            'home_team_id' => $a->id,
            'away_team_id' => $b->id,
            'status' => GameMatch::STATUS_SCHEDULED,
            'home_score' => null,
            'away_score' => null,
        ]);
        GameMatch::factory()->create([
            'competition_id' => $competition->id,
            'home_team_id' => $a->id,
            'away_team_id' => $b->id,
            'status' => GameMatch::STATUS_CANCELLED,
        ]);
        GameMatch::factory()->create([
            'competition_id' => $competition->id,
            'home_team_id' => $a->id,
            'away_team_id' => $b->id,
            'status' => GameMatch::STATUS_POSTPONED,
        ]);

        $standings = $this->service->calculate($competition)->keyBy('team.name');

        $this->assertSame(0, $standings['A']['played']);
        $this->assertSame(0, $standings['B']['played']);
    }

    public function test_calculate_by_group_isolates_standings_per_group_and_ignores_cross_group_matches(): void
    {
        $competition = $this->competition();
        $p = $this->team($competition, 'P');
        $q = $this->team($competition, 'Q');
        $r = $this->team($competition, 'R');
        $s = $this->team($competition, 'S');
        $p->update(['group_label' => 'A']);
        $q->update(['group_label' => 'A']);
        $r->update(['group_label' => 'B']);
        $s->update(['group_label' => 'B']);

        $this->finishedMatch($competition, $p, $q, 2, 0); // intra-poule A
        $this->finishedMatch($competition, $r, $s, 1, 1); // intra-poule B
        // Match manuel hors poule (P de la poule A contre R de la poule B) : ne doit compter
        // dans le classement d'aucune des deux poules.
        $this->finishedMatch($competition, $q, $r, 3, 0);

        $byGroup = $this->service->calculateByGroup($competition);

        $this->assertSame(['A', 'B'], $byGroup->keys()->all());

        $groupA = $byGroup['A']->keyBy('team.name');
        $this->assertSame(1, $groupA['P']['played']);
        $this->assertSame(1, $groupA['Q']['played']);
        $this->assertSame(3, $groupA['P']['points']);

        $groupB = $byGroup['B']->keyBy('team.name');
        $this->assertSame(1, $groupB['R']['played']);
        $this->assertSame(1, $groupB['S']['played']);
    }

    public function test_excludes_teams_whose_registration_is_not_approved(): void
    {
        $competition = $this->competition();
        $approved = $this->team($competition, 'Approved');
        Team::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Pending',
            'registration_status' => Team::REGISTRATION_PENDING,
        ]);

        $standings = $this->service->calculate($competition);

        $this->assertCount(1, $standings);
        $this->assertSame('Approved', $standings->first()['team']->name);
    }
}
