<?php

namespace App\Policies;

use App\Models\GameMatch;
use App\Models\User;

class GameMatchPolicy
{
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOrganisateur();
    }

    public function update(User $user, GameMatch $match): bool
    {
        return $user->isSuperAdmin() || $match->competition->isOrganizedBy($user);
    }

    /**
     * Saisie et validation d'un résultat (RG10 : réservée à l'organisateur habilité, action tracée).
     */
    public function validateResult(User $user, GameMatch $match): bool
    {
        return $this->update($user, $match);
    }

    public function delete(User $user, GameMatch $match): bool
    {
        return $this->update($user, $match);
    }
}
