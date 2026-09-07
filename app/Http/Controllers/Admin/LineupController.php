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
 * Étape 2 du triptyque avant un match : la composition répartit titulaires (11 exactement)
 * et remplaçants parmi les joueurs déjà convoqués (étape 1, ConvocationController) — elle ne
 * peut ni ajouter ni retirer de joueur par rapport à la convocation publiée.
 */
class LineupController extends Controller
{
    public const REQUIRED_STARTERS = 11;

    public function __construct(private readonly SuspensionService $suspensionService)
    {
    }

    public function edit(GameMatch $match): View|RedirectResponse
    {
        $this->authorize('update', $match);

        abort_if($match->isBye(), 404, "Cette équipe est exemptée : il n'y a pas de composition à saisir.");

        $match->load(['homeTeam', 'awayTeam', 'lineups.player']);

        if ($redirect = $this->redirectIfNotConvoked($match)) {
            return $redirect;
        }

        $suspensions = $this->suspensionService->suspendedPlayerIdsForMatch($match);

        return view('admin.matches.lineup', compact('match', 'suspensions'));
    }

    public function update(Request $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('update', $match);

        abort_if($match->isBye(), 404, "Cette équipe est exemptée : il n'y a pas de composition à saisir.");

        $match->load('lineups');

        if ($redirect = $this->redirectIfNotConvoked($match)) {
            return $redirect;
        }

        $validated = $request->validate([
            'home' => ['array'],
            'home.*' => ['required', 'in:titulaire,remplacant'],
            'away' => ['array'],
            'away.*' => ['required', 'in:titulaire,remplacant'],
        ]);

        $suspensions = $this->suspensionService->suspendedPlayerIdsForMatch($match);

        foreach (['home' => $match->home_team_id, 'away' => $match->away_team_id] as $side => $teamId) {
            $convokedIds = $match->lineups->where('team_id', $teamId)->pluck('player_id')->sort()->values()->all();
            $selections = $validated[$side] ?? [];
            $selectedIds = collect(array_map('intval', array_keys($selections)))->sort()->values()->all();
            $label = $side === 'home' ? 'domicile' : 'extérieur';

            // La composition doit attribuer un rôle à chaque joueur convoqué, ni plus ni moins :
            // les clés du formulaire ne peuvent pas être utilisées pour ajouter un joueur non
            // convoqué (étape 1) ni en écarter un sans repasser par une nouvelle convocation.
            if ($selectedIds !== $convokedIds) {
                abort(422, "La composition doit attribuer un rôle à chaque joueur convoqué pour l'équipe {$label}, sans ajout ni retrait.");
            }

            $suspendedSelected = array_intersect($selectedIds, $suspensions->keys()->all());

            if (! empty($suspendedSelected)) {
                $names = Player::whereIn('id', $suspendedSelected)->get()->map->fullName()->join(', ');

                return back()->withErrors([$side => "Joueur(s) suspendu(s) ne pouvant être aligné(s) : {$names}."])->withInput();
            }

            $starterIds = collect($selections)->filter(fn ($status) => $status === 'titulaire')->keys()->map(fn ($id) => (int) $id);

            if ($starterIds->count() !== self::REQUIRED_STARTERS) {
                return back()->withErrors([$side => 'Une équipe doit compter exactement '.self::REQUIRED_STARTERS." titulaires (équipe {$label}), actuellement {$starterIds->count()}."])->withInput();
            }

            // Une équipe sur le terrain compte toujours exactement un gardien parmi ses titulaires.
            if (! Player::whereIn('id', $starterIds)->where('position', Player::POSITION_GOALKEEPER)->exists()) {
                return back()->withErrors([$side => "La composition doit inclure au moins un gardien de but parmi les titulaires (équipe {$label})."])->withInput();
            }

            // Le nombre de remplaçants (convoqués - titulaires) est nécessairement >= 7 dès lors
            // que la convocation exige déjà 18 joueurs minimum et que les titulaires sont fixés
            // à 11 : aucune vérification distincte n'est donc nécessaire ici.
        }

        DB::transaction(function () use ($match, $validated) {
            foreach (['home', 'away'] as $side) {
                foreach (($validated[$side] ?? []) as $playerId => $status) {
                    Lineup::where('match_id', $match->id)
                        ->where('player_id', $playerId)
                        ->update(['is_starting' => $status === 'titulaire']);
                }
            }
        });

        ActivityLog::record('lineup.published', $match, "Composition publiée pour le match #{$match->id}", competitionId: $match->competition_id);

        return redirect()->route('admin.competitions.matches.index', $match->competition_id)
            ->with('status', 'Composition publiée.');
    }

    private function redirectIfNotConvoked(GameMatch $match): ?RedirectResponse
    {
        foreach ([$match->home_team_id, $match->away_team_id] as $teamId) {
            if ($match->lineups->where('team_id', $teamId)->count() < ConvocationController::MIN_CONVOKED) {
                return redirect()->route('admin.matches.convocation.edit', $match)
                    ->with('error', 'Vous devez d\'abord publier une convocation d\'au moins '.ConvocationController::MIN_CONVOKED.' joueurs par équipe avant d\'établir la composition.');
            }
        }

        return null;
    }
}
