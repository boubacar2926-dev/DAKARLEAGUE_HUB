<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\MatchEvent;
use App\Models\Player;
use Illuminate\Support\Collection;

/**
 * Règlement disciplinaire standard du football amateur :
 *  - un carton rouge (direct) entraîne une suspension automatique pour le(s) prochain(s) match(s) ;
 *  - deux cartons jaunes dans le même match valent expulsion et déclenchent la même suspension
 *    qu'un carton rouge direct ;
 *  - l'accumulation de cartons jaunes sur la compétition (seuil configurable, 3 par défaut)
 *    déclenche une suspension d'un match, puis le compteur de jaunes repart à zéro.
 *
 * Ces seuils sont configurables par compétition (Competition::yellow_card_suspension_threshold
 * et red_card_suspension_matches) pour s'adapter au règlement propre de chaque championnat.
 */
class SuspensionService
{
    /**
     * Statut de suspension de chaque joueur d'une équipe pour un match donné.
     *
     * @return Collection<int, array{suspended: bool, reason: ?string}> indexé par player_id
     */
    public function statusForTeam(GameMatch $match, int $teamId): Collection
    {
        $competition = $match->competition;
        $playerIds = Player::where('team_id', $teamId)->pluck('id');
        $priorMatches = $this->priorFinishedMatches($match, $teamId);

        if ($priorMatches->isEmpty()) {
            return $playerIds->mapWithKeys(fn ($id) => [$id => ['suspended' => false, 'reason' => null]]);
        }

        $events = MatchEvent::whereIn('match_id', $priorMatches->pluck('id'))
            ->whereIn('player_id', $playerIds)
            ->whereIn('type', [MatchEvent::TYPE_YELLOW_CARD, MatchEvent::TYPE_RED_CARD])
            ->get()
            ->groupBy('player_id');

        return $playerIds->mapWithKeys(function ($playerId) use ($events, $priorMatches, $competition) {
            $playerEvents = $events->get($playerId, collect());

            return [$playerId => $this->computeSuspension($playerEvents, $priorMatches, $competition)];
        });
    }

    public function isSuspended(Player $player, GameMatch $match): bool
    {
        return (bool) ($this->statusForPlayer($player, $match)['suspended'] ?? false);
    }

    /**
     * @return array{suspended: bool, reason: ?string}
     */
    public function statusForPlayer(Player $player, GameMatch $match): array
    {
        return $this->statusForTeam($match, $player->team_id)->get($player->id)
            ?? ['suspended' => false, 'reason' => null];
    }

    /**
     * IDs des joueurs suspendus (des deux équipes) pour ce match — pratique pour filtrer
     * une liste de composition sans recalculer équipe par équipe.
     *
     * @return Collection<int, string> reason indexé par player_id
     */
    public function suspendedPlayerIdsForMatch(GameMatch $match): Collection
    {
        // union() (et non merge()) : les clés sont les player_id, merge() les renumériterait
        // comme un array_merge() classique et ferait perdre l'association joueur → statut.
        return $this->statusForTeam($match, $match->home_team_id)
            ->union($this->statusForTeam($match, $match->away_team_id))
            ->filter(fn ($status) => $status['suspended'])
            ->map(fn ($status) => $status['reason']);
    }

    /**
     * Matchs déjà disputés (terminés) par cette équipe, dans l'ordre chronologique de la
     * compétition (journée puis ordre de création), strictement avant le match donné.
     *
     * @return Collection<int, GameMatch>
     */
    private function priorFinishedMatches(GameMatch $match, int $teamId): Collection
    {
        $teamMatches = GameMatch::where('competition_id', $match->competition_id)
            ->where(fn ($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->orderByRaw('round is null, round asc, id asc')
            ->get();

        $targetIndex = $teamMatches->search(fn (GameMatch $m) => $m->id === $match->id);

        $priorMatches = $targetIndex === false
            ? $teamMatches
            : $teamMatches->slice(0, $targetIndex);

        return $priorMatches->where('status', GameMatch::STATUS_FINISHED)->values();
    }

    /**
     * @param  Collection<int, MatchEvent>  $playerEvents  Cartons du joueur sur les matchs antérieurs.
     * @param  Collection<int, GameMatch>  $priorMatches  Matchs antérieurs, dans l'ordre chronologique.
     */
    private function computeSuspension(Collection $playerEvents, Collection $priorMatches, Competition $competition): array
    {
        $suspensionQueue = 0;
        $yellowCounter = 0;
        $reason = null;

        foreach ($priorMatches as $priorMatch) {
            if ($suspensionQueue > 0) {
                $suspensionQueue--;
            }

            $matchEvents = $playerEvents->where('match_id', $priorMatch->id);
            $yellows = $matchEvents->where('type', MatchEvent::TYPE_YELLOW_CARD)->count();
            $reds = $matchEvents->where('type', MatchEvent::TYPE_RED_CARD)->count();
            $isExpelled = $reds > 0 || $yellows >= 2;

            if ($isExpelled) {
                $suspensionQueue += max(1, $competition->red_card_suspension_matches);
                $yellowCounter = 0;
                $reason = $reds > 0
                    ? 'carton rouge direct'
                    : 'expulsion (2 cartons jaunes dans un même match)';
            } elseif ($yellows === 1) {
                $yellowCounter++;

                if ($yellowCounter >= $competition->yellow_card_suspension_threshold) {
                    $suspensionQueue += 1;
                    $yellowCounter = 0;
                    $reason = "cumul de {$competition->yellow_card_suspension_threshold} cartons jaunes";
                }
            }
        }

        return [
            'suspended' => $suspensionQueue > 0,
            'reason' => $suspensionQueue > 0 ? $reason : null,
        ];
    }
}
