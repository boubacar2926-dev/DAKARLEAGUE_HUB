<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * Génère le calendrier (round-robin) d'une compétition à partir de ses équipes inscrites.
     * Formats gérés : aller_simple (chaque équipe rencontre les autres une fois)
     * et aller_retour (rencontres retour avec domicile/extérieur inversés).
     *
     * @return int Nombre de matchs créés.
     */
    public function generate(Competition $competition): int
    {
        $teamIds = $competition->teams()->approved()->pluck('id')->all();

        if (count($teamIds) < 2) {
            throw new \InvalidArgumentException("Au moins deux équipes validées sont nécessaires pour générer un calendrier.");
        }

        $fixtures = $this->roundRobinFixtures($teamIds);

        if ($competition->format === Competition::FORMAT_DOUBLE_ROUND) {
            $fixtures = $fixtures->concat($this->secondLeg($fixtures));
        }

        $created = 0;

        foreach ($fixtures as $fixture) {
            GameMatch::create([
                'competition_id' => $competition->id,
                'home_team_id' => $fixture['home'],
                'away_team_id' => $fixture['away'],
                'round' => $fixture['round'],
                'status' => GameMatch::STATUS_SCHEDULED,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Empêche deux matchs au même créneau pour une même équipe (règle du calendrier).
     */
    public function hasSchedulingConflict(GameMatch $match, \DateTimeInterface $scheduledAt): bool
    {
        return GameMatch::where('competition_id', $match->competition_id)
            ->when($match->exists, fn ($query) => $query->where('id', '!=', $match->id))
            ->where('scheduled_at', $scheduledAt)
            ->where(function ($query) use ($match) {
                $query->where('home_team_id', $match->home_team_id)
                    ->orWhere('away_team_id', $match->home_team_id)
                    ->orWhere('home_team_id', $match->away_team_id)
                    ->orWhere('away_team_id', $match->away_team_id);
            })
            ->exists();
    }

    /**
     * @param  array<int, int>  $teamIds
     * @return Collection<int, array{round: int, home: int, away: int}>
     */
    private function roundRobinFixtures(array $teamIds): Collection
    {
        if (count($teamIds) % 2 !== 0) {
            $teamIds[] = 0; // "bye" fictif si nombre impair d'équipes (0 = pas d'équipe)
        }

        $rounds = count($teamIds) - 1;
        $half = count($teamIds) / 2;
        $fixtures = collect();

        for ($round = 0; $round < $rounds; $round++) {
            for ($i = 0; $i < $half; $i++) {
                $home = $teamIds[$i];
                $away = $teamIds[count($teamIds) - 1 - $i];

                if ($home !== 0 && $away !== 0) {
                    $fixtures->push(['round' => $round + 1, 'home' => $home, 'away' => $away]);
                }
            }

            array_splice($teamIds, 1, 0, array_splice($teamIds, -1, 1));
        }

        return $fixtures;
    }

    /**
     * @param  Collection<int, array{round: int, home: int, away: int}>  $firstLeg
     * @return Collection<int, array{round: int, home: int, away: int}>
     */
    private function secondLeg(Collection $firstLeg): Collection
    {
        $offset = $firstLeg->max('round');

        return $firstLeg->map(fn (array $fixture) => [
            'round' => $fixture['round'] + $offset,
            'home' => $fixture['away'],
            'away' => $fixture['home'],
        ]);
    }
}
