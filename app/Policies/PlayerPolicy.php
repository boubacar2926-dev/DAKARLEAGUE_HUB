<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

class PlayerPolicy
{
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOrganisateur() || $user->isResponsable();
    }

    public function update(User $user, Player $player): bool
    {
        if ($user->isSuperAdmin() || $player->competition->isOrganizedBy($user)) {
            return true;
        }

        return $user->isResponsable()
            && $player->team->manager_user_id === $user->id
            && $player->competition->allow_team_managers_to_manage_players;
    }

    public function delete(User $user, Player $player): bool
    {
        return $this->update($user, $player);
    }
}
