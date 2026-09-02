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
    public function edit(GameMatch $match): View
    {
        $this->authorize('validateResult', $match);

        $match->load(['homeTeam.players', 'awayTeam.players', 'events']);

        return view('admin.matches.result', compact('match'));
    }

    /**
     * Saisie/validation du résultat : score, buts et cartons (RG05, RG07, RG10).
     */
    public function update(StoreMatchResultRequest $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('validateResult', $match);

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
}
