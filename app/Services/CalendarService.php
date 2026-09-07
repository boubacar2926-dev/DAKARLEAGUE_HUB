<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * Génère le calendrier d'une compétition à partir de ses équipes validées, selon son format :
     *  - aller_simple / aller_retour : championnat round-robin (chacun rencontre chacun, une ou deux fois).
     *  - poules : tirage au sort des équipes dans N poules, championnat aller simple au sein de chaque poule.
     *  - elimination_directe : tirage au sort d'un tableau à élimination directe (1er tour uniquement).
     *
     * @return int Nombre de matchs créés.
     */
    public function generate(Competition $competition): int
    {
        $teamIds = $competition->teams()->approved()->pluck('id')->all();

        return match ($competition->format) {
            Competition::FORMAT_SINGLE_ROUND, Competition::FORMAT_DOUBLE_ROUND => $this->generateRoundRobin($competition, $teamIds),
            Competition::FORMAT_GROUPS => $this->generateGroups($competition, $teamIds),
            Competition::FORMAT_KNOCKOUT => $this->generateKnockoutFirstRound($competition, $teamIds),
            default => throw new \InvalidArgumentException("Le format « {$competition->format} » n'est pas pris en charge pour la génération automatique du calendrier."),
        };
    }

    /**
     * Fait progresser un tableau à élimination directe : apparie les vainqueurs du tour en
     * cours pour créer le tour suivant. Nécessite que tous les matchs du tour en cours soient
     * terminés sur un vainqueur désigné (score décisif ou tirs au but en cas d'égalité).
     *
     * @return int Nombre de matchs créés pour le nouveau tour.
     */
    public function generateNextKnockoutRound(Competition $competition): int
    {
        if (! $competition->isKnockoutFormat()) {
            throw new \InvalidArgumentException("Cette action n'est disponible que pour une compétition à élimination directe.");
        }

        $currentRound = (int) $competition->matches()->max('round');

        if ($currentRound === 0) {
            throw new \InvalidArgumentException("Générez d'abord le premier tour du tableau.");
        }

        $matches = $competition->matches()->where('round', $currentRound)->orderBy('id')->get();

        if ($matches->count() < 2) {
            throw new \InvalidArgumentException("Le tour actuel est la finale : il n'y a pas de tour suivant.");
        }

        if ($matches->contains(fn (GameMatch $match) => $match->status !== GameMatch::STATUS_FINISHED)) {
            throw new \InvalidArgumentException("Tous les matchs du tour en cours doivent être terminés avant de générer le tour suivant.");
        }

        $winnerIds = $matches->map(function (GameMatch $match) {
            $winnerId = $match->winnerTeamId();

            if ($winnerId === null) {
                throw new \InvalidArgumentException("Le match #{$match->id} est terminé sur un score nul sans séance de tirs au but désignant un vainqueur : impossible de faire progresser le tableau.");
            }

            return $winnerId;
        });

        $created = 0;

        for ($i = 0; $i < $winnerIds->count(); $i += 2) {
            GameMatch::create([
                'competition_id' => $competition->id,
                'home_team_id' => $winnerIds[$i],
                'away_team_id' => $winnerIds[$i + 1],
                'round' => $currentRound + 1,
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

    private function generateRoundRobin(Competition $competition, array $teamIds): int
    {
        if (count($teamIds) < 2) {
            throw new \InvalidArgumentException("Au moins deux équipes validées sont nécessaires pour générer un calendrier.");
        }

        $fixtures = $this->roundRobinFixtures($teamIds);

        if ($competition->format === Competition::FORMAT_DOUBLE_ROUND) {
            $fixtures = $fixtures->concat($this->secondLeg($fixtures));
        }

        return $this->createMatches($competition, $fixtures);
    }

    /**
     * Répartit les équipes par tirage au sort dans N poules puis génère un championnat
     * aller simple à l'intérieur de chaque poule (les poules ne s'affrontent pas entre elles).
     */
    private function generateGroups(Competition $competition, array $teamIds): int
    {
        $numberOfGroups = $competition->number_of_groups ?? 2;

        if (count($teamIds) < $numberOfGroups * 2) {
            throw new \InvalidArgumentException(
                "Il faut au moins ".($numberOfGroups * 2)." équipes validées pour former {$numberOfGroups} poules d'au moins 2 équipes."
            );
        }

        shuffle($teamIds); // tirage au sort, comme pour une vraie compétition amateur

        $labels = range('A', 'Z');
        $groups = [];

        foreach ($teamIds as $index => $teamId) {
            $groups[$index % $numberOfGroups][] = $teamId;
        }

        $created = 0;

        foreach ($groups as $groupIndex => $groupTeamIds) {
            Team::whereIn('id', $groupTeamIds)->update(['group_label' => $labels[$groupIndex]]);

            if (count($groupTeamIds) < 2) {
                continue; // poule incomplète : aucun match possible, mais ne bloque pas les autres poules
            }

            $created += $this->createMatches($competition, $this->roundRobinFixtures($groupTeamIds));
        }

        return $created;
    }

    /**
     * Tire au sort le tableau à élimination directe et crée uniquement le 1er tour : les tours
     * suivants dépendent des vainqueurs et sont générés via generateNextKnockoutRound().
     *
     * Si le nombre d'équipes n'est pas une puissance de 2, les équipes en trop reçoivent un
     * "exempté" (bye) tiré au sort : qualifiées d'office pour le tour 2 sans jouer de match,
     * comme dans un vrai tournoi amateur — plutôt que d'imposer artificiellement 2, 4, 8, 16…
     */
    private function generateKnockoutFirstRound(Competition $competition, array $teamIds): int
    {
        $count = count($teamIds);

        if ($count < 2) {
            throw new \InvalidArgumentException("Au moins deux équipes validées sont nécessaires pour générer un tableau à élimination directe.");
        }

        shuffle($teamIds); // tirage au sort

        $bracketSize = $this->nextPowerOfTwo($count);
        $byeTeamIds = array_splice($teamIds, 0, $bracketSize - $count);

        $fixtures = collect();

        foreach ($byeTeamIds as $teamId) {
            $fixtures->push(['round' => 1, 'home' => $teamId, 'away' => null]);
        }

        for ($i = 0; $i < count($teamIds); $i += 2) {
            $fixtures->push(['round' => 1, 'home' => $teamIds[$i], 'away' => $teamIds[$i + 1]]);
        }

        return $this->createMatches($competition, $fixtures);
    }

    private function nextPowerOfTwo(int $n): int
    {
        $power = 1;

        while ($power < $n) {
            $power *= 2;
        }

        return $power;
    }

    /**
     * @param  Collection<int, array{round: int, home: int, away: ?int}>  $fixtures
     */
    private function createMatches(Competition $competition, Collection $fixtures): int
    {
        $created = 0;

        foreach ($fixtures as $fixture) {
            // Un "away" absent signifie un exempté (bye) : aucun match à jouer, l'équipe est
            // déjà qualifiée pour le tour suivant.
            $isBye = $fixture['away'] === null;

            GameMatch::create([
                'competition_id' => $competition->id,
                'home_team_id' => $fixture['home'],
                'away_team_id' => $fixture['away'],
                'round' => $fixture['round'],
                'status' => $isBye ? GameMatch::STATUS_FINISHED : GameMatch::STATUS_SCHEDULED,
                'validated_at' => $isBye ? now() : null,
            ]);
            $created++;
        }

        return $created;
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
