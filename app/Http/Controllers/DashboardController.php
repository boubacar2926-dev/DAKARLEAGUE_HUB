<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isResponsable()) {
            return $this->responsableDashboard($user);
        }

        if ($user->isJoueur()) {
            return $this->joueurDashboard($user);
        }

        return view('dashboard');
    }

    private function responsableDashboard(User $user): View
    {
        $teams = $user->managedTeams()->with(['competition', 'players'])->get();

        $upcomingMatches = GameMatch::with(['homeTeam', 'awayTeam', 'competition'])
            ->where(function ($q) use ($teams) {
                $q->whereIn('home_team_id', $teams->pluck('id'))
                    ->orWhereIn('away_team_id', $teams->pluck('id'));
            })
            ->where('status', GameMatch::STATUS_SCHEDULED)
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        return view('dashboard-responsable', compact('teams', 'upcomingMatches'));
    }

    private function joueurDashboard(User $user): View
    {
        $players = $user->playerProfiles()->with(['team.competition'])->get();

        $stats = $players->map(function ($player) {
            $goals = $player->matchEvents()->whereIn('type', \App\Models\MatchEvent::GOAL_TYPES)->count();
            $yellow = $player->matchEvents()->where('type', \App\Models\MatchEvent::TYPE_YELLOW_CARD)->count();
            $red = $player->matchEvents()->where('type', \App\Models\MatchEvent::TYPE_RED_CARD)->count();

            return ['player' => $player, 'goals' => $goals, 'yellow' => $yellow, 'red' => $red];
        });

        $teamIds = $players->pluck('team_id');

        $upcomingMatches = GameMatch::with(['homeTeam', 'awayTeam', 'competition'])
            ->where(function ($q) use ($teamIds) {
                $q->whereIn('home_team_id', $teamIds)
                    ->orWhereIn('away_team_id', $teamIds);
            })
            ->where('status', GameMatch::STATUS_SCHEDULED)
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        return view('dashboard-joueur', compact('stats', 'upcomingMatches'));
    }
}
