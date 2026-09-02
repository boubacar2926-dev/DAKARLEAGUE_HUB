<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompetitionRequest;
use App\Models\ActivityLog;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $competitions = $user->isSuperAdmin()
            ? Competition::withCount('teams')->latest()->paginate(10)
            : Competition::withCount('teams')
                ->where(fn ($q) => $q->where('created_by', $user->id)
                    ->orWhereHas('organizers', fn ($q) => $q->where('users.id', $user->id)))
                ->latest()
                ->paginate(10);

        return view('admin.competitions.index', compact('competitions'));
    }

    public function create(): View
    {
        $this->authorize('create', Competition::class);

        return view('admin.competitions.create');
    }

    public function store(StoreCompetitionRequest $request): RedirectResponse
    {
        $this->authorize('create', Competition::class);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('competitions', 'public');
        }

        $data['created_by'] = $request->user()->id;

        $competition = Competition::create($data);
        $competition->organizers()->attach($request->user()->id);

        ActivityLog::record('competition.created', $competition, "Création de la compétition « {$competition->name} »", competitionId: $competition->id);

        return redirect()->route('admin.competitions.show', $competition)
            ->with('status', "Compétition créée avec succès.");
    }

    public function show(Competition $competition): View
    {
        $this->authorize('view', $competition);

        $competition->loadCount(['teams', 'players', 'matches']);
        $matchesPlayed = $competition->matches()->where('status', 'termine')->count();
        $matchesScheduled = $competition->matches()->where('status', 'programme')->count();
        $totalGoals = \App\Models\MatchEvent::whereIn('match_id', $competition->matches()->pluck('id'))
            ->whereIn('type', \App\Models\MatchEvent::GOAL_TYPES)
            ->count();

        return view('admin.competitions.show', compact('competition', 'matchesPlayed', 'matchesScheduled', 'totalGoals'));
    }

    public function edit(Competition $competition): View
    {
        $this->authorize('update', $competition);

        return view('admin.competitions.edit', compact('competition'));
    }

    public function update(StoreCompetitionRequest $request, Competition $competition): RedirectResponse
    {
        $this->authorize('update', $competition);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($competition->logo_path) {
                Storage::disk('public')->delete($competition->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('competitions', 'public');
        }

        $competition->update($data);

        ActivityLog::record('competition.updated', $competition, "Modification de la compétition « {$competition->name} »", competitionId: $competition->id);

        return redirect()->route('admin.competitions.show', $competition)
            ->with('status', "Compétition mise à jour.");
    }

    public function destroy(Competition $competition): RedirectResponse
    {
        $this->authorize('delete', $competition);

        $name = $competition->name;
        $competition->delete();

        ActivityLog::record('competition.deleted', $competition, "Suppression de la compétition « {$name} »");

        return redirect()->route('admin.competitions.index')
            ->with('status', "Compétition « {$name} » supprimée.");
    }
}
