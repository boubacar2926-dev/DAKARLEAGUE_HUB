<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Models\ActivityLog;
use App\Models\Competition;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Competition $competition): View
    {
        $this->authorize('view', $competition);

        $teams = $competition->teams()->withCount('players')->orderBy('name')->get();

        return view('admin.teams.index', compact('competition', 'teams'));
    }

    public function create(Competition $competition): View
    {
        $this->authorizeCompetitionManager($competition);

        return view('admin.teams.create', compact('competition'));
    }

    public function store(StoreTeamRequest $request, Competition $competition): RedirectResponse
    {
        $this->authorizeCompetitionManager($competition);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('teams', 'public');
        }

        $data['competition_id'] = $competition->id;

        $team = Team::create($data);

        ActivityLog::record('team.created', $team, "Ajout de l'équipe « {$team->name} »", competitionId: $competition->id);

        return redirect()->route('admin.competitions.teams.index', $competition)
            ->with('status', "Équipe « {$team->name} » ajoutée.");
    }

    public function edit(Team $team): View
    {
        $this->authorize('update', $team);

        return view('admin.teams.edit', compact('team'));
    }

    public function update(StoreTeamRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($team->logo_path) {
                Storage::disk('public')->delete($team->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('teams', 'public');
        }

        $team->update($data);

        ActivityLog::record('team.updated', $team, "Modification de l'équipe « {$team->name} »", competitionId: $team->competition_id);

        return redirect()->route('admin.competitions.teams.index', $team->competition_id)
            ->with('status', "Équipe mise à jour.");
    }

    /**
     * Validation d'une inscription d'équipe en ligne (fonctionnalité secondaire, §1.4).
     * L'organisateur "valide les équipes" — cf. matrice des rôles §2.1.
     */
    public function approve(Team $team): RedirectResponse
    {
        $this->authorizeCompetitionManager($team->competition);

        $team->update(['registration_status' => Team::REGISTRATION_APPROVED]);

        ActivityLog::record('team.approved', $team, "Inscription validée pour « {$team->name} »", competitionId: $team->competition_id);

        return back()->with('status', "Équipe « {$team->name} » validée.");
    }

    public function reject(Team $team): RedirectResponse
    {
        $this->authorizeCompetitionManager($team->competition);

        $team->update(['registration_status' => Team::REGISTRATION_REJECTED]);

        ActivityLog::record('team.rejected', $team, "Inscription refusée pour « {$team->name} »", competitionId: $team->competition_id);

        return back()->with('status', "Inscription de « {$team->name} » refusée.");
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        $competitionId = $team->competition_id;
        $name = $team->name;
        $team->delete();

        ActivityLog::record('team.deleted', $team, "Suppression de l'équipe « {$name} »", competitionId: $competitionId);

        return redirect()->route('admin.competitions.teams.index', $competitionId)
            ->with('status', "Équipe « {$name} » supprimée.");
    }

    private function authorizeCompetitionManager(Competition $competition): void
    {
        $user = request()->user();
        abort_unless($user->isSuperAdmin() || $competition->isOrganizedBy($user), 403);
    }
}
