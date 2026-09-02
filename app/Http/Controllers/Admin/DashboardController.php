<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $competitions = $user->isSuperAdmin()
            ? Competition::withCount(['teams', 'matches'])->latest()->get()
            : Competition::withCount(['teams', 'matches'])
                ->where(fn ($q) => $q->where('created_by', $user->id)
                    ->orWhereHas('organizers', fn ($q) => $q->where('users.id', $user->id)))
                ->latest()
                ->get();

        $competitionIds = $competitions->pluck('id');

        $upcomingMatches = GameMatch::with(['homeTeam', 'awayTeam', 'competition'])
            ->whereIn('competition_id', $competitionIds)
            ->where('status', GameMatch::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $pendingResults = GameMatch::with(['homeTeam', 'awayTeam', 'competition'])
            ->whereIn('competition_id', $competitionIds)
            ->where('status', GameMatch::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->orderBy('scheduled_at')
            ->get();

        return view('admin.dashboard', compact('competitions', 'upcomingMatches', 'pendingResults'));
    }
}
