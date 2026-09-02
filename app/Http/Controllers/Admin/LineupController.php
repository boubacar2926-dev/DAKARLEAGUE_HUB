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

class LineupController extends Controller
{
    public function __construct(private readonly SuspensionService $suspensionService)
    {
    }

    public function edit(GameMatch $match): View
    {
        $this->authorize('update', $match);

        $match->load(['homeTeam.players', 'awayTeam.players', 'lineups']);

        $lineupsByPlayer = $match->lineups->keyBy('player_id');
        $suspensions = $this->suspensionService->suspendedPlayerIdsForMatch($match);

        return view('admin.matches.lineup', compact('match', 'lineupsByPlayer', 'suspensions'));
    }

    public function update(Request $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('update', $match);

        $validated = $request->validate([
            'home' => ['array'],
            'home.*' => ['nullable', 'in:titulaire,remplacant'],
            'away' => ['array'],
            'away.*' => ['nullable', 'in:titulaire,remplacant'],
        ]);

        $validated['home'] = array_filter($validated['home'] ?? []);
        $validated['away'] = array_filter($validated['away'] ?? []);

        $suspensions = $this->suspensionService->suspendedPlayerIdsForMatch($match);

        foreach (['home' => $match->home_team_id, 'away' => $match->away_team_id] as $side => $teamId) {
            $selections = $validated[$side] ?? [];

            // Empêche d'associer un joueur qui n'appartient pas réellement à cette équipe
            // (les clés du tableau proviennent directement des noms de champs du formulaire).
            $playerIds = array_map('intval', array_keys($selections));
            $validPlayerIds = Player::where('team_id', $teamId)->whereIn('id', $playerIds)->pluck('id')->all();

            if (count($validPlayerIds) !== count($playerIds)) {
                abort(422, "Un ou plusieurs joueurs sélectionnés n'appartiennent pas à l'équipe concernée.");
            }

            // Règlement disciplinaire : un joueur suspendu (carton rouge ou cumul de jaunes)
            // ne peut être convoqué, ni comme titulaire ni comme remplaçant.
            $suspendedSelected = array_intersect($playerIds, $suspensions->keys()->all());

            if (! empty($suspendedSelected)) {
                $names = Player::whereIn('id', $suspendedSelected)->get()->map->fullName()->join(', ');

                return back()->withErrors([$side => "Joueur(s) suspendu(s) ne pouvant être convoqué(s) : {$names}."])->withInput();
            }

            $starters = collect($selections)->filter(fn ($status) => $status === 'titulaire')->count();

            if ($starters > 11) {
                return back()->withErrors([$side => "Une équipe ne peut compter plus de 11 titulaires (".ucfirst($side === 'home' ? 'domicile' : 'extérieur').")."])->withInput();
            }
        }

        DB::transaction(function () use ($match, $validated) {
            Lineup::where('match_id', $match->id)->delete();

            foreach (['home' => $match->home_team_id, 'away' => $match->away_team_id] as $side => $teamId) {
                foreach (($validated[$side] ?? []) as $playerId => $status) {
                    Lineup::create([
                        'match_id' => $match->id,
                        'team_id' => $teamId,
                        'player_id' => $playerId,
                        'is_starting' => $status === 'titulaire',
                    ]);
                }
            }
        });

        ActivityLog::record('lineup.updated', $match, "Composition enregistrée pour le match #{$match->id}", competitionId: $match->competition_id);

        return redirect()->route('admin.competitions.matches.index', $match->competition_id)
            ->with('status', "Composition enregistrée.");
    }
}
