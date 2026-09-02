<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\CalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MatchController extends Controller
{
    public function __construct(private readonly CalendarService $calendarService)
    {
    }

    public function index(Competition $competition): View
    {
        $this->authorize('view', $competition);

        $matches = $competition->matches()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('round')
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('round');

        return view('admin.matches.index', compact('competition', 'matches'));
    }

    public function create(Competition $competition): View
    {
        $this->authorize('manageMatches', $competition);

        $teams = $competition->teams()->approved()->orderBy('name')->get();

        return view('admin.matches.create', compact('competition', 'teams'));
    }

    public function store(Request $request, Competition $competition): RedirectResponse
    {
        $this->authorize('manageMatches', $competition);

        $validated = $request->validate([
            'home_team_id' => ['required', 'different:away_team_id', Rule::exists('teams', 'id')->where('competition_id', $competition->id)->where('registration_status', Team::REGISTRATION_APPROVED)],
            'away_team_id' => ['required', Rule::exists('teams', 'id')->where('competition_id', $competition->id)->where('registration_status', Team::REGISTRATION_APPROVED)],
            'round' => ['nullable', 'integer', 'min:1'],
            'scheduled_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:150'],
        ], [
            'home_team_id.different' => "Un match doit opposer deux équipes distinctes.",
        ]);

        if (! empty($validated['scheduled_at'])) {
            $transient = new GameMatch([
                'competition_id' => $competition->id,
                'home_team_id' => $validated['home_team_id'],
                'away_team_id' => $validated['away_team_id'],
            ]);

            if ($this->calendarService->hasSchedulingConflict($transient, new \DateTime($validated['scheduled_at']))) {
                return back()->withErrors(['scheduled_at' => "Une des deux équipes a déjà un match programmé à ce créneau."])->withInput();
            }
        }

        $match = GameMatch::create($validated + [
            'competition_id' => $competition->id,
            'status' => GameMatch::STATUS_SCHEDULED,
        ]);

        ActivityLog::record('match.created', $match, "Ajout d'un match au calendrier", competitionId: $competition->id);

        return redirect()->route('admin.competitions.matches.index', $competition)
            ->with('status', "Match ajouté au calendrier.");
    }

    public function generate(Competition $competition): RedirectResponse
    {
        $this->authorize('manageMatches', $competition);

        if ($competition->matches()->exists()) {
            return back()->with('error', "Un calendrier existe déjà pour cette compétition. Supprimez-le avant d'en générer un nouveau.");
        }

        try {
            $count = $this->calendarService->generate($competition);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLog::record('calendar.generated', $competition, "Génération automatique du calendrier ({$count} matchs)", competitionId: $competition->id);

        return redirect()->route('admin.competitions.matches.index', $competition)
            ->with('status', "Calendrier généré : {$count} matchs créés.");
    }

    public function edit(GameMatch $match): View
    {
        $this->authorize('update', $match);

        $teams = $match->competition->teams()->orderBy('name')->get();

        return view('admin.matches.edit', compact('match', 'teams'));
    }

    public function update(Request $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('update', $match);

        // Un match déjà validé (résultat saisi, cf. MatchResultController) ne doit plus pouvoir
        // être reprogrammé/reporté/annulé ici : cela le ferait disparaître du classement
        // (StandingsService ne compte que les matchs status=termine) sans purger le score ni
        // l'événement de validation, ce qui corrompt les données. destroy() applique déjà cette
        // règle pour la suppression, update() doit faire de même.
        if ($match->isFinished()) {
            return back()->with('error', "Impossible de modifier un match dont le résultat a déjà été validé.");
        }

        $validated = $request->validate([
            'scheduled_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in([
                GameMatch::STATUS_SCHEDULED,
                GameMatch::STATUS_POSTPONED,
                GameMatch::STATUS_CANCELLED,
            ])],
            'postponed_reason' => ['required_if:status,'.GameMatch::STATUS_POSTPONED, 'nullable', 'string', 'max:500'],
            'cancellation_reason' => ['required_if:status,'.GameMatch::STATUS_CANCELLED, 'nullable', 'string', 'max:500'],
        ]);

        if (! empty($validated['scheduled_at'])
            && $this->calendarService->hasSchedulingConflict($match, new \DateTime($validated['scheduled_at']))) {
            return back()->withErrors(['scheduled_at' => "Une des deux équipes a déjà un match programmé à ce créneau."])->withInput();
        }

        $match->update($validated);

        ActivityLog::record('match.updated', $match, "Modification du match #{$match->id}", competitionId: $match->competition_id);

        return redirect()->route('admin.competitions.matches.index', $match->competition_id)
            ->with('status', "Match mis à jour.");
    }

    public function destroy(GameMatch $match): RedirectResponse
    {
        $this->authorize('delete', $match);

        if ($match->isFinished()) {
            return back()->with('error', "Impossible de supprimer un match déjà validé.");
        }

        $competitionId = $match->competition_id;
        $match->delete();

        ActivityLog::record('match.deleted', $match, "Suppression du match #{$match->id}", competitionId: $competitionId);

        return redirect()->route('admin.competitions.matches.index', $competitionId)
            ->with('status', "Match supprimé.");
    }
}
