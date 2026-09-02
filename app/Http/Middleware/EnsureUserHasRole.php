<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Vérifie que l'utilisateur connecté possède l'un des rôles autorisés
     * (RG12 : un utilisateur accède uniquement aux fonctions autorisées par son rôle).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, "Vous n'avez pas les droits nécessaires pour accéder à cette page.");
        }

        return $next($request);
    }
}
