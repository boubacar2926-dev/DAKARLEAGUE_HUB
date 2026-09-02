<?php

namespace App\Policies;

use App\Models\Competition;
use App\Models\User;

class CompetitionPolicy
{
    /**
     * Consultation du dashboard d'une compétition (RG12 / matrice des droits §2.3).
     */
    public function view(User $user, Competition $competition): bool
    {
        return $user->isSuperAdmin() || $competition->isOrganizedBy($user);
    }

    /**
     * Création d'une compétition : super-admin et organisateur uniquement.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOrganisateur();
    }

    public function update(User $user, Competition $competition): bool
    {
        return $user->isSuperAdmin() || $competition->isOrganizedBy($user);
    }

    public function delete(User $user, Competition $competition): bool
    {
        return $user->isSuperAdmin() || $competition->isOrganizedBy($user);
    }

    /**
     * Gestion du calendrier, des matchs et validation des résultats.
     */
    public function manageMatches(User $user, Competition $competition): bool
    {
        return $user->isSuperAdmin() || $competition->isOrganizedBy($user);
    }

    /**
     * Gestion des utilisateurs autorisés à organiser la compétition.
     */
    public function manageOrganizers(User $user, Competition $competition): bool
    {
        return $user->isSuperAdmin();
    }
}
