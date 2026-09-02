<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\MatchEvent;
use App\Models\Player;
use Illuminate\Support\Collection;

class CompetitionStatsService
{
    /**
     * Top buteurs (buts + penalties, hors contre-son-camp) — §2.4-E.
     *
     * @return Collection<int, array{player: Player, goals: int}>
     */
    public function topScorers(Competition $competition, int $limit = 10): Collection
    {
        $matchIds = $competition->matches()->where('status', 'termine')->pluck('id');

        return MatchEvent::whereIn('match_id', $matchIds)
            ->whereIn('type', [MatchEvent::TYPE_GOAL, MatchEvent::TYPE_PENALTY_GOAL])
            ->whereNotNull('player_id')
            ->selectRaw('player_id, COUNT(*) as goals')
            ->groupBy('player_id')
            ->orderByDesc('goals')
            ->limit($limit)
            ->with('player.team')
            ->get()
            ->filter(fn ($row) => $row->player?->team?->isApproved())
            ->map(fn ($row) => ['player' => $row->player, 'goals' => (int) $row->goals])
            ->values();
    }

    /**
     * Joueurs les plus sanctionnés (cartons jaunes/rouges) — §2.4-E.
     *
     * @return Collection<int, array{player: Player, yellow: int, red: int}>
     */
    public function topCards(Competition $competition, int $limit = 10): Collection
    {
        $matchIds = $competition->matches()->where('status', 'termine')->pluck('id');

        $events = MatchEvent::whereIn('match_id', $matchIds)
            ->whereIn('type', [MatchEvent::TYPE_YELLOW_CARD, MatchEvent::TYPE_RED_CARD])
            ->whereNotNull('player_id')
            ->with('player.team')
            ->get()
            ->groupBy('player_id');

        return $events->map(function (Collection $playerEvents) {
            $player = $playerEvents->first()->player;
            $redCards = $playerEvents->where('type', MatchEvent::TYPE_RED_CARD)->count();

            // Deux cartons jaunes dans le même match valent expulsion (= carton rouge), au
            // même titre qu'un rouge direct — distinction affichée pour éviter toute confusion
            // avec un simple cumul de jaunes sur plusieurs matchs.
            $expelledViaSecondYellow = $playerEvents
                ->where('type', MatchEvent::TYPE_YELLOW_CARD)
                ->groupBy('match_id')
                ->filter(fn (Collection $matchYellows) => $matchYellows->count() >= 2)
                ->isNotEmpty();

            return [
                'player' => $player,
                'yellow' => $playerEvents->where('type', MatchEvent::TYPE_YELLOW_CARD)->count(),
                'red' => $redCards,
                'expelled' => $redCards > 0 || $expelledViaSecondYellow,
            ];
        })
            ->filter(fn ($row) => $row['player']?->team?->isApproved())
            ->sortByDesc(fn ($row) => $row['yellow'] + $row['red'] * 3)
            ->take($limit)
            ->values();
    }
}
