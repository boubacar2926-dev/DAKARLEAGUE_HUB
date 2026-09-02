<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreTeamRegistrationRequest;
use App\Models\ActivityLog;
use App\Models\Competition;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamRegistrationController extends Controller
{
    /**
     * Inscription en ligne d'une équipe (fonctionnalité secondaire, §1.4) —
     * l'organisateur devra ensuite la valider (§2.1 : "Organisateur ... valide les équipes").
     */
    public function create(Competition $competition): View
    {
        $this->authorizeRegistration($competition);

        return view('public.competitions.register-team', compact('competition'));
    }

    public function store(StoreTeamRegistrationRequest $request, Competition $competition): RedirectResponse
    {
        $this->authorizeRegistration($competition);

        $user = $request->user();

        $team = Team::create($request->validated() + [
            'competition_id' => $competition->id,
            'manager_user_id' => $user->id,
            'manager_name' => $user->name,
            'registration_status' => Team::REGISTRATION_PENDING,
        ]);

        if ($user->role === User::ROLE_JOUEUR) {
            // Affectation directe (hors mass assignment) : 'role' est volontairement exclu
            // de User::$fillable pour ne jamais pouvoir être défini depuis une entrée utilisateur.
            $user->role = User::ROLE_RESPONSABLE;
            $user->save();
        }

        ActivityLog::record('team.registered', $team, "Inscription en ligne de « {$team->name} » par {$user->name}", competitionId: $competition->id);

        return redirect()->route('public.competitions.show', $competition)
            ->with('status', "Votre équipe « {$team->name} » a été soumise et attend la validation de l'organisateur.");
    }

    private function authorizeRegistration(Competition $competition): void
    {
        abort_unless($competition->status === Competition::STATUS_REGISTRATION_OPEN, 404);
    }
}
