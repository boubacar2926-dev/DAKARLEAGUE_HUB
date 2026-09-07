<?php

namespace Tests\Unit\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalendarService;
    }

    private function competitionWithTeams(int $count, string $format = Competition::FORMAT_SINGLE_ROUND): Competition
    {
        $competition = Competition::factory()->create(['format' => $format]);

        Team::factory()->count($count)->create([
            'competition_id' => $competition->id,
            'registration_status' => Team::REGISTRATION_APPROVED,
        ]);

        return $competition;
    }

    public function test_generates_every_pairing_exactly_once_for_single_round_with_even_teams(): void
    {
        $competition = $this->competitionWithTeams(4);

        $created = $this->service->generate($competition);

        $this->assertSame(6, $created); // 4 * 3 / 2
        $this->assertNoTeamPlaysItself($competition);
        $this->assertEachPairMeetsExactly($competition, 1);
    }

    public function test_generates_every_pairing_exactly_once_for_single_round_with_odd_teams(): void
    {
        $competition = $this->competitionWithTeams(5);

        $created = $this->service->generate($competition);

        $this->assertSame(10, $created); // 5 * 4 / 2
        $this->assertNoTeamPlaysItself($competition);
        $this->assertEachPairMeetsExactly($competition, 1);
    }

    public function test_double_round_doubles_fixtures_with_reversed_home_away(): void
    {
        $competition = $this->competitionWithTeams(4, Competition::FORMAT_DOUBLE_ROUND);

        $created = $this->service->generate($competition);

        $this->assertSame(12, $created); // 2 * (4 * 3 / 2)
        $this->assertNoTeamPlaysItself($competition);
        $this->assertEachPairMeetsExactly($competition, 2);

        // Every pair must meet once with each side at home.
        $matches = $competition->matches()->get();
        foreach ($matches as $match) {
            $reverseExists = $matches->contains(
                fn (GameMatch $m) => $m->home_team_id === $match->away_team_id
                    && $m->away_team_id === $match->home_team_id
            );
            $this->assertTrue($reverseExists, 'Each pairing must be played home and away.');
        }
    }

    public function test_requires_at_least_two_approved_teams(): void
    {
        $competition = $this->competitionWithTeams(1);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->generate($competition);
    }

    public function test_ignores_teams_that_are_not_approved(): void
    {
        $competition = Competition::factory()->create(['format' => Competition::FORMAT_SINGLE_ROUND]);
        Team::factory()->count(2)->create([
            'competition_id' => $competition->id,
            'registration_status' => Team::REGISTRATION_APPROVED,
        ]);
        Team::factory()->create([
            'competition_id' => $competition->id,
            'registration_status' => Team::REGISTRATION_PENDING,
        ]);

        $created = $this->service->generate($competition);

        $this->assertSame(1, $created); // only the 2 approved teams face off
    }

    public function test_rejects_unknown_formats(): void
    {
        // Format bidon non persistable (l'enum SQL n'accepte que les 4 formats connus) :
        // on construit le modèle en mémoire pour exercer uniquement le garde-fou du service.
        $competition = Competition::factory()->make(['format' => 'championnat_a_points']);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->generate($competition);
    }

    public function test_groups_format_splits_teams_into_groups_by_draw(): void
    {
        $competition = Competition::factory()->create([
            'format' => Competition::FORMAT_GROUPS,
            'number_of_groups' => 2,
        ]);
        Team::factory()->count(8)->create([
            'competition_id' => $competition->id,
            'registration_status' => Team::REGISTRATION_APPROVED,
        ]);

        $created = $this->service->generate($competition);

        // 2 poules de 4 : 4*3/2 = 6 matchs par poule, aucune rencontre entre poules différentes.
        $this->assertSame(12, $created);
        $this->assertNoTeamPlaysItself($competition);

        $teams = $competition->teams()->approved()->get();
        $this->assertSame(['A', 'B'], $teams->pluck('group_label')->unique()->sort()->values()->all());
        $this->assertCount(4, $teams->where('group_label', 'A'));
        $this->assertCount(4, $teams->where('group_label', 'B'));

        foreach ($competition->matches()->with(['homeTeam', 'awayTeam'])->get() as $match) {
            $this->assertSame(
                $match->homeTeam->group_label,
                $match->awayTeam->group_label,
                'A group-stage match must never cross two different groups.'
            );
        }
    }

    public function test_groups_format_requires_enough_teams_for_the_requested_number_of_groups(): void
    {
        $competition = Competition::factory()->create([
            'format' => Competition::FORMAT_GROUPS,
            'number_of_groups' => 3,
        ]);
        Team::factory()->count(4)->create([
            'competition_id' => $competition->id,
            'registration_status' => Team::REGISTRATION_APPROVED,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->generate($competition);
    }

    public function test_knockout_format_pairs_teams_for_a_single_first_round(): void
    {
        $competition = $this->competitionWithTeams(8, Competition::FORMAT_KNOCKOUT);

        $created = $this->service->generate($competition);

        $this->assertSame(4, $created);
        $this->assertNoTeamPlaysItself($competition);

        $matches = $competition->matches()->get();
        $this->assertTrue($matches->every(fn (GameMatch $m) => $m->round === 1));

        $teamIds = $competition->teams()->approved()->pluck('id');
        $pairedIds = $matches->flatMap(fn (GameMatch $m) => [$m->home_team_id, $m->away_team_id]);
        $this->assertSame($teamIds->sort()->values()->all(), $pairedIds->sort()->values()->all(), 'Every team must appear exactly once in round 1.');
    }

    public function test_knockout_format_requires_at_least_two_teams(): void
    {
        $competition = $this->competitionWithTeams(1, Competition::FORMAT_KNOCKOUT);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->generate($competition);
    }

    /**
     * Un nombre d'équipes qui n'est pas une puissance de 2 (ici 6) doit être accepté : les
     * équipes en trop (8 - 6 = 2, la puissance de 2 supérieure) reçoivent un "exempté" (bye)
     * tiré au sort, qualifiées d'office pour le tour 2 sans jouer de match — comme dans un
     * vrai tournoi amateur, plutôt que d'imposer artificiellement 4 ou 8 équipes.
     */
    public function test_knockout_format_assigns_byes_when_team_count_is_not_a_power_of_two(): void
    {
        $competition = $this->competitionWithTeams(6, Competition::FORMAT_KNOCKOUT);

        $created = $this->service->generate($competition);

        $round1 = $competition->matches()->where('round', 1)->get();
        // P/2 = 8/2 = 4 "emplacements" au 1er tour : 2 matchs réels + 2 exemptés.
        $this->assertSame(4, $created);
        $this->assertCount(4, $round1);

        $byes = $round1->filter(fn (GameMatch $m) => $m->isBye());
        $realMatches = $round1->filter(fn (GameMatch $m) => ! $m->isBye());
        $this->assertCount(2, $byes);
        $this->assertCount(2, $realMatches);

        // Un exempté est déjà "terminé" avec sa propre équipe pour vainqueur, sans score.
        foreach ($byes as $bye) {
            $this->assertSame(GameMatch::STATUS_FINISHED, $bye->status);
            $this->assertSame($bye->home_team_id, $bye->winnerTeamId());
        }

        $this->assertTrue($realMatches->every(fn (GameMatch $m) => $m->status === GameMatch::STATUS_SCHEDULED));

        // Chaque équipe validée apparaît exactement une fois au 1er tour (match ou exempté).
        $teamIds = $competition->teams()->approved()->pluck('id');
        $appearances = $round1->flatMap(fn (GameMatch $m) => array_filter([$m->home_team_id, $m->away_team_id]));
        $this->assertSame($teamIds->sort()->values()->all(), $appearances->sort()->values()->all());
    }

    public function test_generates_next_knockout_round_combining_byes_and_round1_winners(): void
    {
        $competition = $this->competitionWithTeams(6, Competition::FORMAT_KNOCKOUT);
        $this->service->generate($competition);

        $realMatches = $competition->matches()->where('round', 1)->whereNotNull('away_team_id')->get();
        foreach ($realMatches as $match) {
            $match->update(['status' => GameMatch::STATUS_FINISHED, 'home_score' => 2, 'away_score' => 0]);
        }

        $created = $this->service->generateNextKnockoutRound($competition);

        // Round 2 doit réunir les 2 vainqueurs des matchs réels et les 2 exemptés : 4 équipes, 2 matchs.
        $this->assertSame(2, $created);
        $round2 = $competition->matches()->where('round', 2)->get();
        $this->assertCount(2, $round2);
        $this->assertTrue($round2->every(fn (GameMatch $m) => ! $m->isBye()));

        $expectedAdvancing = $realMatches->pluck('home_team_id')
            ->merge($competition->matches()->where('round', 1)->whereNull('away_team_id')->pluck('home_team_id'))
            ->sort()->values();
        $actualAdvancing = $round2->flatMap(fn (GameMatch $m) => [$m->home_team_id, $m->away_team_id])->sort()->values();
        $this->assertSame($expectedAdvancing->all(), $actualAdvancing->all());
    }

    public function test_generates_next_knockout_round_by_pairing_winners(): void
    {
        $competition = $this->competitionWithTeams(4, Competition::FORMAT_KNOCKOUT);
        $this->service->generate($competition);

        $round1 = $competition->matches()->orderBy('id')->get();
        $round1[0]->update(['status' => GameMatch::STATUS_FINISHED, 'home_score' => 2, 'away_score' => 1]);
        $round1[1]->update(['status' => GameMatch::STATUS_FINISHED, 'home_score' => 0, 'away_score' => 3]);

        $created = $this->service->generateNextKnockoutRound($competition);

        $this->assertSame(1, $created);
        $final = $competition->matches()->where('round', 2)->sole();
        $this->assertSame($round1[0]->home_team_id, $final->home_team_id);
        $this->assertSame($round1[1]->away_team_id, $final->away_team_id);
    }

    public function test_cannot_generate_next_knockout_round_before_current_round_is_finished(): void
    {
        $competition = $this->competitionWithTeams(4, Competition::FORMAT_KNOCKOUT);
        $this->service->generate($competition);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->generateNextKnockoutRound($competition);
    }

    public function test_cannot_generate_next_knockout_round_without_a_penalty_shootout_winner_on_a_draw(): void
    {
        $competition = $this->competitionWithTeams(2, Competition::FORMAT_KNOCKOUT);
        $this->service->generate($competition);

        $competition->matches()->update(['status' => GameMatch::STATUS_FINISHED, 'home_score' => 1, 'away_score' => 1]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->generateNextKnockoutRound($competition);
    }

    public function test_generates_next_knockout_round_using_penalty_shootout_winner_on_a_draw(): void
    {
        $competition = $this->competitionWithTeams(4, Competition::FORMAT_KNOCKOUT);
        $this->service->generate($competition);

        $round1 = $competition->matches()->orderBy('id')->get();
        $round1[0]->update(['status' => GameMatch::STATUS_FINISHED, 'home_score' => 1, 'away_score' => 1, 'home_penalties' => 5, 'away_penalties' => 4]);
        $round1[1]->update(['status' => GameMatch::STATUS_FINISHED, 'home_score' => 2, 'away_score' => 0]);

        $created = $this->service->generateNextKnockoutRound($competition);

        $this->assertSame(1, $created);
        $final = $competition->matches()->where('round', 2)->sole();
        $this->assertSame($round1[0]->home_team_id, $final->home_team_id);
    }

    public function test_final_round_has_no_next_round_to_generate(): void
    {
        $competition = $this->competitionWithTeams(2, Competition::FORMAT_KNOCKOUT);
        $this->service->generate($competition);
        $competition->matches()->update(['status' => GameMatch::STATUS_FINISHED, 'home_score' => 1, 'away_score' => 0]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->generateNextKnockoutRound($competition);
    }

    public function test_detects_scheduling_conflict_for_same_team_same_slot(): void
    {
        $competition = Competition::factory()->create();
        $slot = now()->addWeek();

        $existing = GameMatch::factory()->create([
            'competition_id' => $competition->id,
            'scheduled_at' => $slot,
        ]);

        $candidate = GameMatch::factory()->make([
            'competition_id' => $competition->id,
            'home_team_id' => $existing->away_team_id, // shares a team with $existing
        ]);
        $candidate->exists = false;

        $this->assertTrue($this->service->hasSchedulingConflict($candidate, $slot));
    }

    public function test_no_conflict_when_teams_or_slot_differ(): void
    {
        $competition = Competition::factory()->create();
        $slot = now()->addWeek();

        GameMatch::factory()->create([
            'competition_id' => $competition->id,
            'scheduled_at' => $slot,
        ]);

        $candidate = GameMatch::factory()->create([
            'competition_id' => $competition->id,
            'scheduled_at' => $slot->copy()->addHour(),
        ]);

        $this->assertFalse($this->service->hasSchedulingConflict($candidate, $slot->copy()->addHour()));
    }

    public function test_editing_a_match_does_not_conflict_with_itself(): void
    {
        $competition = Competition::factory()->create();
        $slot = now()->addWeek();

        $match = GameMatch::factory()->create([
            'competition_id' => $competition->id,
            'scheduled_at' => $slot,
        ]);

        $this->assertFalse($this->service->hasSchedulingConflict($match, $slot));
    }

    private function assertNoTeamPlaysItself(Competition $competition): void
    {
        foreach ($competition->matches()->get() as $match) {
            $this->assertNotSame($match->home_team_id, $match->away_team_id, 'A team must never play itself.');
        }
    }

    private function assertEachPairMeetsExactly(Competition $competition, int $times): void
    {
        $matches = $competition->matches()->get();
        $teamIds = $competition->teams()->approved()->pluck('id');

        foreach ($teamIds as $i => $teamA) {
            foreach ($teamIds as $teamB) {
                if ($teamA === $teamB) {
                    continue;
                }

                $meetings = $matches->filter(
                    fn (GameMatch $m) => ($m->home_team_id === $teamA && $m->away_team_id === $teamB)
                        || ($m->home_team_id === $teamB && $m->away_team_id === $teamA)
                )->count();

                $this->assertSame($times, $meetings, "Teams {$teamA} and {$teamB} should meet exactly {$times} time(s).");
            }
        }
    }
}
