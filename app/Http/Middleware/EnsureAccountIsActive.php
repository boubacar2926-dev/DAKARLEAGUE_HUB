<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Coupe immédiatement une session déjà ouverte si le compte est désactivé entre-temps
     * (LoginRequest bloque déjà la connexion, mais ne suffit pas pour une session en cours).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Ce compte a été désactivé.');
        }

        return $next($request);
    }
}
