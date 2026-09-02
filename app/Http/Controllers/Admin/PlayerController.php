<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlayerRequest;
use App\Models\ActivityLog;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function index(Team $team): View
    {
        // Un responsable habilité à gérer son équipe (cf. TeamPolicy::update) doit pouvoir
        // consulter son effectif — vérifier uniquement l'accès à la compétition (organisateur/
        // admin) l'en empêchait à tort alors qu'il peut par ailleurs créer/modifier ses joueurs.
        $this->authorize('update', $team);

        $players = $team->players()->orderBy('jersey_number')->get();

        return view('admin.players.index', compact('team', 'players'));
    }

    public function create(Team $team): View
    {
        $this->authorize('update', $team);

        return view('admin.players.create', compact('team'));
    }

    public function store(StorePlayerRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('players', 'public');
        }

        $data['team_id'] = $team->id;
        $data['competition_id'] = $team->competition_id;

        $player = Player::create($data);

        ActivityLog::record('player.created', $player, "Ajout du joueur {$player->fullName()} à « {$team->name} »", competitionId: $team->competition_id);

        return redirect()->route('admin.teams.players.index', $team)
            ->with('status', "Joueur {$player->fullName()} ajouté.");
    }

    public function edit(Player $player): View
    {
        $this->authorize('update', $player);

        return view('admin.players.edit', compact('player'));
    }

    public function update(StorePlayerRequest $request, Player $player): RedirectResponse
    {
        $this->authorize('update', $player);

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($player->photo_path) {
                Storage::disk('public')->delete($player->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('players', 'public');
        }

        $player->update($data);

        ActivityLog::record('player.updated', $player, "Modification du joueur {$player->fullName()}", competitionId: $player->competition_id);

        return redirect()->route('admin.teams.players.index', $player->team_id)
            ->with('status', "Joueur mis à jour.");
    }

    public function destroy(Player $player): RedirectResponse
    {
        $this->authorize('delete', $player);

        $teamId = $player->team_id;
        $name = $player->fullName();
        $competitionId = $player->competition_id;
        $player->delete();

        ActivityLog::record('player.deleted', $player, "Suppression du joueur {$name}", competitionId: $competitionId);

        return redirect()->route('admin.teams.players.index', $teamId)
            ->with('status', "Joueur {$name} supprimé.");
    }
}
