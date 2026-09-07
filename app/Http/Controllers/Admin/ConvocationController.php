<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GameMatch;
use App\Models\Lineup;
use App\Models\Player;
use App\Services\SuspensionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Étape 1 du triptyque avant un match : la convocation (liste des joueurs appelés,
 * 18 minimum par équipe) doit être publiée avant que la composition (étape 2, qui
 * répartit titulaires/remplaçants parmi les convoqués) puisse être établie.
 */
class ConvocationController extends Controller
{
    public const MIN_CONVOKED = 18;

    public function __construct(private readonly SuspensionService $suspensionService)
    {
    }

    public function edit(GameMatch $match): View
    {
        $this->authorize('update', $match);

        abort_if($match->isBye(), 404, "Cette équipe est exemptée : il n'y a pas de convocation à saisir.");

        $match->load(['homeTeam.players', 'awayTeam.players', 'lineups']);

        $convokedIds = $match->lineups->pluck('player_id')->all();
        $suspensions = $this->suspensionService->suspendedPlayerIdsForMatch($match);

        return view('admin.matches.convocation', compact('match', 'convokedIds', 'suspensions'));
    }

    public function update(Request $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('update', $match);

        abort_if($match->isBye(), 404, "Cette équipe est exemptée : il n'y a pas de convocation à saisir.");

        $validated = $request->validate([
            'home' => ['array'],
            'home.*' => ['integer'],
            'away' => ['array'],
            'away.*' => ['integer'],
        ]);

        $suspensions = $this->suspensionService->suspendedPlayerIdsForMatch($match);
        $counts = [];

        foreach (['home' => $match->home_team_id, 'away' => $match->away_team_id] as $side => $teamId) {
            $playerIds = array_map('intval', $validated[$side] ?? []);
            $label = $side === 'home' ? 'domicile' : 'extérieur';

            // Les identifiants soumis proviennent de cases à cocher côté client : on revérifie
            // qu'ils appartiennent réellement à l'équipe concernée avant de les persister.
            $validPlayerIds = Player::where('team_id', $teamId)->whereIn('id', $playerIds)->pluck('id')->all();

            if (count($validPlayerIds) !== count($playerIds)) {
                abort(422, "Un ou plusieurs joueurs sélectionnés n'appartiennent pas à l'équipe concernée.");
            }

            if (count($playerIds) < self::MIN_CONVOKED) {
                return back()->withErrors([$side => 'La convocation doit compter au moins '.self::MIN_CONVOKED." joueurs (équipe {$label}), actuellement ".count($playerIds).'.'])->withInput();
            }

            $suspendedSelected = array_intersect($playerIds, $suspensions->keys()->all());

            if (! empty($suspendedSelected)) {
                $names = Player::whereIn('id', $suspendedSelected)->get()->map->fullName()->join(', ');

                return back()->withErrors([$side => "Joueur(s) suspendu(s) ne pouvant être convoqué(s) : {$names}."])->withInput();
            }

            $counts[$side] = count($playerIds);
        }

        DB::transaction(function () use ($match, $validated) {
            // Republier la convocation invalide la composition précédente : elle portait sur
            // un ensemble de joueurs qui n'est plus celui retenu.
            Lineup::where('match_id', $match->id)->delete();

            foreach (['home' => $match->home_team_id, 'away' => $match->away_team_id] as $side => $teamId) {
                foreach (($validated[$side] ?? []) as $playerId) {
                    Lineup::create([
                        'match_id' => $match->id,
                        'team_id' => $teamId,
                        'player_id' => $playerId,
                        'is_starting' => false,
                    ]);
                }
            }
        });

        ActivityLog::record(
            'convocation.published',
            $match,
            "Convocation publiée pour le match #{$match->id} ({$counts['home']} + {$counts['away']} joueurs)",
            competitionId: $match->competition_id
        );

        return redirect()->route('admin.matches.lineup.edit', $match)
            ->with('status', 'Convocation publiée. Vous pouvez maintenant établir la composition.');
    }
}
