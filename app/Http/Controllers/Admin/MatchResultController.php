<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMatchResultRequest;
use App\Models\ActivityLog;
use App\Models\GameMatch;
use App\Models\MatchEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MatchResultController extends Controller
{
    public function edit(GameMatch $match): View|RedirectResponse
    {
        $this->authorize('validateResult', $match);

        abort_if($match->isBye(), 404, "Cette équipe est exemptée : il n'y a pas de match à saisir.");

        if ($redirect = $this->redirectIfLineupsIncomplete($match)) {
            return $redirect;
        }

        $match->load(['homeTeam.players', 'awayTeam.players', 'lineups.player', 'events']);

        // N'autoriser comme buteur/fauteur que les joueurs réellement convoqués pour ce match
        // (titulaires + remplaçants) — sinon un but pourrait être crédité à un joueur resté au
        // repos. Repli sur l'effectif complet pour les matchs antérieurs à cette règle, dépourvus
        // de composition enregistrée, afin de ne pas bloquer la correction de leur résultat.
        $homePlayers = $this->eligibleScorers($match, $match->home_team_id, $match->homeTeam->players);
        $awayPlayers = $this->eligibleScorers($match, $match->away_team_id, $match->awayTeam->players);

        return view('admin.matches.result', compact('match', 'homePlayers', 'awayPlayers'));
    }

    private function eligibleScorers(GameMatch $match, int $teamId, $fallbackRoster)
    {
        $convoked = $match->lineups->where('team_id', $teamId)->map->player->filter();

        return $convoked->isNotEmpty() ? $convoked->values() : $fallbackRoster;
    }

    /**
     * Saisie/validation du résultat : score, buts et cartons (RG05, RG07, RG10).
     */
    public function update(StoreMatchResultRequest $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('validateResult', $match);

        abort_if($match->isBye(), 404, "Cette équipe est exemptée : il n'y a pas de match à saisir.");

        if ($redirect = $this->redirectIfLineupsIncomplete($match)) {
            return $redirect;
        }

        // Un match reporté ou annulé n'a pas été joué : y saisir un score le ferait basculer en
        // "terminé" (et donc entrer dans le classement via StandingsService) alors qu'il n'a pas
        // eu lieu, contredisant sa reprogrammation/annulation.
        if (in_array($match->status, [GameMatch::STATUS_CANCELLED, GameMatch::STATUS_POSTPONED], true)) {
            return back()->with('error', "Impossible de saisir un résultat pour un match reporté ou annulé.");
        }

        $validated = $request->validated();

        DB::transaction(function () use ($match, $validated) {
            MatchEvent::where('match_id', $match->id)->delete();

            foreach ($validated['events'] ?? [] as $event) {
                MatchEvent::create([
                    'match_id' => $match->id,
                    'team_id' => $event['team_id'],
                    'player_id' => $event['player_id'] ?? null,
                    'type' => $event['type'],
                    'minute' => $event['minute'] ?? null,
                ]);
            }

            $match->update([
                'home_score' => $validated['home_score'],
                'away_score' => $validated['away_score'],
                'home_penalties' => $validated['home_penalties'] ?? null,
                'away_penalties' => $validated['away_penalties'] ?? null,
                'status' => GameMatch::STATUS_FINISHED,
                'validated_at' => now(),
                'validated_by' => request()->user()->id,
            ]);
        });

        ActivityLog::record(
            'match.result_validated',
            $match,
            "Validation du résultat : {$validated['home_score']}-{$validated['away_score']}",
            $validated,
            $match->competition_id
        );

        return redirect()->route('admin.competitions.matches.index', $match->competition_id)
            ->with('status', "Résultat enregistré et classement mis à jour.");
    }

    /**
     * Un résultat déjà validé peut toujours être corrigé même si, pour une raison ou une autre,
     * sa convocation/composition d'origine ne respecterait plus les règles actuelles — on ne
     * bloque cette vérification que pour la toute première saisie du résultat.
     */
    private function redirectIfLineupsIncomplete(GameMatch $match): ?RedirectResponse
    {
        if ($match->isFinished()) {
            return null;
        }

        $match->loadMissing('lineups');

        foreach ([$match->home_team_id, $match->away_team_id] as $teamId) {
            $starters = $match->lineups->where('team_id', $teamId)->where('is_starting', true)->count();
            $substitutes = $match->lineups->where('team_id', $teamId)->where('is_starting', false)->count();

            if ($starters !== LineupController::REQUIRED_STARTERS || $substitutes < ConvocationController::MIN_CONVOKED - LineupController::REQUIRED_STARTERS) {
                return redirect()->route('admin.competitions.matches.index', $match->competition_id)
                    ->with('error', 'Vous devez d\'abord publier la convocation (18 joueurs min.) et la composition ('.LineupController::REQUIRED_STARTERS.' titulaires + '.(ConvocationController::MIN_CONVOKED - LineupController::REQUIRED_STARTERS).' remplaçants min.) des deux équipes avant de saisir le résultat.');
            }
        }

        return null;
    }
}
