<?php

namespace Tests\Concerns;

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;

/**
 * Saisir un résultat exige désormais une convocation (18 joueurs min.) puis une composition
 * (11 titulaires) publiées pour les deux équipes — ce helper reproduit ce préalable via les
 * vraies routes HTTP pour les tests qui ont seulement besoin d'un match prêt à être joué.
 */
trait PublishesMatchLineups
{
    protected function publishLineups(GameMatch $match, User $actingUser): void
    {
        $homePlayers = $this->squadOfEighteen($match->home_team_id, $match->competition_id);
        $awayPlayers = $this->squadOfEighteen($match->away_team_id, $match->competition_id);

        $this->actingAs($actingUser)->put(route('admin.matches.convocation.update', $match), [
            'home' => $homePlayers->pluck('id')->all(),
            'away' => $awayPlayers->pluck('id')->all(),
        ])->assertSessionDoesntHaveErrors();

        $this->actingAs($actingUser)->put(route('admin.matches.lineup.update', $match), [
            'home' => $this->startersAndSubs($homePlayers),
            'away' => $this->startersAndSubs($awayPlayers),
        ])->assertSessionDoesntHaveErrors();
    }

    /**
     * 18 joueurs dont un gardien en première position, pour que startersAndSubs() (qui prend
     * les 11 premiers comme titulaires) satisfasse toujours la règle "au moins un gardien".
     *
     * @return \Illuminate\Support\Collection<int, Player>
     */
    private function squadOfEighteen(int $teamId, int $competitionId)
    {
        $goalkeeper = Player::factory()->create([
            'team_id' => $teamId,
            'competition_id' => $competitionId,
            'position' => Player::POSITION_GOALKEEPER,
        ]);

        $others = Player::factory()->count(17)->create([
            'team_id' => $teamId,
            'competition_id' => $competitionId,
        ]);

        return collect([$goalkeeper])->concat($others);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Player>  $players
     * @return array<int, string>
     */
    private function startersAndSubs($players): array
    {
        // union() (et non merge()) : les clés sont des player_id (entiers) — merge() les
        // aurait réindexées comme un array_merge() classique, faussant l'association joueur → rôle.
        return $players->take(11)->mapWithKeys(fn (Player $p) => [$p->id => 'titulaire'])
            ->union($players->skip(11)->mapWithKeys(fn (Player $p) => [$p->id => 'remplacant']))
            ->all();
    }
}
