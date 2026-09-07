<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\MatchEvent;
use Illuminate\Support\Collection;

class StandingsService
{
    /**
     * Calcule le classement d'une compétition à partir des matchs terminés
     * (RG07 : seuls les matchs terminés et validés sont pris en compte).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function calculate(Competition $competition): Collection
    {
        return $this->calculateForTeams($competition, $competition->teams()->approved()->get());
    }

    /**
     * Classement par poule (format "poules") : chaque groupe tiré au sort par CalendarService
     * a son propre mini-championnat, les équipes de poules différentes ne s'affrontant jamais.
     *
     * @return Collection<string, Collection<int, array<string, mixed>>> indexé par lettre de poule
     */
    public function calculateByGroup(Competition $competition): Collection
    {
        return $competition->teams()->approved()->get()
            ->groupBy(fn ($team) => $team->group_label ?? '?')
            ->sortKeys()
            ->map(fn ($teams) => $this->calculateForTeams($competition, $teams));
    }

    /**
     * @param  Collection<int, \App\Models\Team>  $teams
     * @return Collection<int, array<string, mixed>>
     */
    private function calculateForTeams(Competition $competition, Collection $teams): Collection
    {
        $teamIds = $teams->pluck('id');

        $rows = $teams->keyBy('id')->map(fn ($team) => [
            'team' => $team,
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'points' => 0,
            'yellow_cards' => 0,
            'red_cards' => 0,
            'form' => [],
        ]);

        // whereIn sur les deux équipes : un match ne compte pour ce groupe de teamIds que si
        // ses DEUX équipes en font partie (essentiel pour isoler le classement d'une poule).
        $matches = $competition->matches()
            ->where('status', GameMatch::STATUS_FINISHED)
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->whereIn('home_team_id', $teamIds)
            ->whereIn('away_team_id', $teamIds)
            ->get();

        foreach ($matches as $match) {
            $this->applyMatchResult($rows, $match, $competition);
        }

        $this->applyDisciplinaryStats($rows, $matches->pluck('id'));

        $headToHead = $this->buildHeadToHead($matches, $competition);

        return $rows->values()
            ->sortBy([
                fn ($a, $b) => $b['points'] <=> $a['points'],
                fn ($a, $b) => $this->goalDifference($b) <=> $this->goalDifference($a),
                fn ($a, $b) => $b['goals_for'] <=> $a['goals_for'],
                fn ($a, $b) => $this->fairPlayScore($a) <=> $this->fairPlayScore($b),
                // RG09 : confrontation directe — dernier recours entre deux équipes toujours à égalité.
                fn ($a, $b) => $this->headToHeadPoints($headToHead, $b['team']->id, $a['team']->id)
                    <=> $this->headToHeadPoints($headToHead, $a['team']->id, $b['team']->id),
                fn ($a, $b) => $this->headToHeadGoalDiff($headToHead, $b['team']->id, $a['team']->id)
                    <=> $this->headToHeadGoalDiff($headToHead, $a['team']->id, $b['team']->id),
            ])
            ->values()
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;
                $row['goal_difference'] = $this->goalDifference($row);
                $row['form'] = array_slice($row['form'], -5);

                return $row;
            });
    }

    private function applyMatchResult(Collection $rows, GameMatch $match, Competition $competition): void
    {
        $home = $rows->get($match->home_team_id);
        $away = $rows->get($match->away_team_id);

        if (! $home || ! $away) {
            return;
        }

        $home['played']++;
        $away['played']++;
        $home['goals_for'] += $match->home_score;
        $home['goals_against'] += $match->away_score;
        $away['goals_for'] += $match->away_score;
        $away['goals_against'] += $match->home_score;

        if ($match->home_score > $match->away_score) {
            $home['won']++;
            $home['points'] += $competition->points_win;
            $away['lost']++;
            $away['points'] += $competition->points_loss;
            $home['form'][] = 'V';
            $away['form'][] = 'D';
        } elseif ($match->home_score < $match->away_score) {
            $away['won']++;
            $away['points'] += $competition->points_win;
            $home['lost']++;
            $home['points'] += $competition->points_loss;
            $away['form'][] = 'V';
            $home['form'][] = 'D';
        } else {
            $home['drawn']++;
            $away['drawn']++;
            $home['points'] += $competition->points_draw;
            $away['points'] += $competition->points_draw;
            $home['form'][] = 'N';
            $away['form'][] = 'N';
        }

        $rows->put($match->home_team_id, $home);
        $rows->put($match->away_team_id, $away);
    }

    private function applyDisciplinaryStats(Collection $rows, Collection $matchIds): void
    {
        if ($matchIds->isEmpty()) {
            return;
        }

        $cardsByTeam = MatchEvent::whereIn('match_id', $matchIds)
            ->whereIn('type', [MatchEvent::TYPE_YELLOW_CARD, MatchEvent::TYPE_RED_CARD])
            ->get()
            ->groupBy('team_id');

        foreach ($cardsByTeam as $teamId => $events) {
            $row = $rows->get($teamId);

            if (! $row) {
                continue;
            }

            $row['yellow_cards'] = $events->where('type', MatchEvent::TYPE_YELLOW_CARD)->count();
            $row['red_cards'] = $events->where('type', MatchEvent::TYPE_RED_CARD)->count();
            $rows->put($teamId, $row);
        }
    }

    private function goalDifference(array $row): int
    {
        return $row['goals_for'] - $row['goals_against'];
    }

    private function fairPlayScore(array $row): int
    {
        return $row['yellow_cards'] + ($row['red_cards'] * 3);
    }

    /**
     * Construit, pour chaque paire d'équipes s'étant affrontées, les points et la
     * différence de buts obtenus l'une contre l'autre (confrontation directe, RG09).
     *
     * @return array<int, array<int, array{points: int, goal_diff: int}>>
     */
    private function buildHeadToHead(Collection $matches, Competition $competition): array
    {
        $headToHead = [];

        $add = function (int $teamId, int $opponentId, int $goalsFor, int $goalsAgainst, int $points) use (&$headToHead) {
            $current = $headToHead[$teamId][$opponentId] ?? ['points' => 0, 'goal_diff' => 0];
            $headToHead[$teamId][$opponentId] = [
                'points' => $current['points'] + $points,
                'goal_diff' => $current['goal_diff'] + ($goalsFor - $goalsAgainst),
            ];
        };

        foreach ($matches as $match) {
            if ($match->home_score > $match->away_score) {
                [$homePoints, $awayPoints] = [$competition->points_win, $competition->points_loss];
            } elseif ($match->home_score < $match->away_score) {
                [$homePoints, $awayPoints] = [$competition->points_loss, $competition->points_win];
            } else {
                [$homePoints, $awayPoints] = [$competition->points_draw, $competition->points_draw];
            }

            $add($match->home_team_id, $match->away_team_id, $match->home_score, $match->away_score, $homePoints);
            $add($match->away_team_id, $match->home_team_id, $match->away_score, $match->home_score, $awayPoints);
        }

        return $headToHead;
    }

    private function headToHeadPoints(array $headToHead, int $teamId, int $opponentId): int
    {
        return $headToHead[$teamId][$opponentId]['points'] ?? 0;
    }

    private function headToHeadGoalDiff(array $headToHead, int $teamId, int $opponentId): int
    {
        return $headToHead[$teamId][$opponentId]['goal_diff'] ?? 0;
    }
}
