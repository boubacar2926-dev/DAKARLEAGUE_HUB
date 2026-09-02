<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function create(User $user, ?Team $team = null): bool
    {
        return $user->isSuperAdmin() || $user->isOrganisateur();
    }

    public function update(User $user, Team $team): bool
    {
        if ($user->isSuperAdmin() || $team->competition->isOrganizedBy($user)) {
            return true;
        }

        return $this->isManagerWithPermission($user, $team);
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->isSuperAdmin() || $team->competition->isOrganizedBy($user);
    }

    /**
     * Un responsable ne peut gérer son équipe que si la compétition l'y autorise
     * (permission activable par compétition, décision de conception §3.3).
     */
    private function isManagerWithPermission(User $user, Team $team): bool
    {
        return $user->isResponsable()
            && $team->manager_user_id === $user->id
            && $team->competition->allow_team_managers_to_manage_players;
    }
}
