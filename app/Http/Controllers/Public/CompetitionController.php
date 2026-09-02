<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Team;
use App\Services\CompetitionStatsService;
use App\Services\StandingsService;
use App\Services\SuspensionService;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function index(): View
    {
        $competitions = Competition::published()
            ->withCount(['teams' => fn ($q) => $q->approved()])
            ->orderByDesc('start_date')
            ->paginate(12);

        return view('public.competitions.index', compact('competitions'));
    }

    public function show(Competition $competition): View
    {
        $this->abortIfNotPublished($competition);

        $competition->loadCount(['teams' => fn ($q) => $q->approved()]);

        $nextMatches = $competition->matches()
            ->with(['homeTeam', 'awayTeam'])
            ->where('status', GameMatch::STATUS_SCHEDULED)
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $lastResults = $competition->matches()
            ->with(['homeTeam', 'awayTeam'])
            ->where('status', GameMatch::STATUS_FINISHED)
            ->orderByDesc('validated_at')
            ->limit(5)
            ->get();

        return view('public.competitions.show', compact('competition', 'nextMatches', 'lastResults'));
    }

    public function standings(Competition $competition, StandingsService $standingsService): View
    {
        $this->abortIfNotPublished($competition);

        $standings = $standingsService->calculate($competition);

        return view('public.competitions.standings', compact('competition', 'standings'));
    }

    public function stats(Competition $competition, StandingsService $standingsService, CompetitionStatsService $statsService): View
    {
        $this->abortIfNotPublished($competition);

        $standings = $standingsService->calculate($competition);
        $topScorers = $statsService->topScorers($competition);
        $topCards = $statsService->topCards($competition);
        $bestAttack = $standings->sortByDesc('goals_for')->first();
        $bestDefense = $standings->sortBy('goals_against')->first();

        return view('public.competitions.stats', compact('competition', 'topScorers', 'topCards', 'bestAttack', 'bestDefense'));
    }

    public function calendar(Competition $competition): View
    {
        $this->abortIfNotPublished($competition);

        $matches = $competition->matches()
            ->with(['homeTeam', 'awayTeam'])
            ->whereHas('homeTeam', fn ($q) => $q->approved())
            ->whereHas('awayTeam', fn ($q) => $q->approved())
            ->orderBy('round')
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('round');

        return view('public.competitions.calendar', compact('competition', 'matches'));
    }

    public function results(Competition $competition): View
    {
        $this->abortIfNotPublished($competition);

        $matches = $competition->matches()
            ->with(['homeTeam', 'awayTeam', 'events.player', 'lineups.player'])
            ->whereHas('homeTeam', fn ($q) => $q->approved())
            ->whereHas('awayTeam', fn ($q) => $q->approved())
            ->where('status', GameMatch::STATUS_FINISHED)
            ->orderByDesc('validated_at')
            ->paginate(15);

        return view('public.competitions.results', compact('competition', 'matches'));
    }

    public function team(Competition $competition, Team $team): View
    {
        $this->abortIfNotPublished($competition);
        abort_unless($team->competition_id === $competition->id && $team->isApproved(), 404);

        $team->load(['players' => fn ($q) => $q->orderBy('jersey_number')]);

        return view('public.competitions.team', compact('competition', 'team'));
    }

    public function player(Competition $competition, Player $player, SuspensionService $suspensionService): View
    {
        $this->abortIfNotPublished($competition);
        $player->loadMissing('team');
        abort_unless($player->competition_id === $competition->id && $player->team?->isApproved(), 404);

        $goals = $player->matchEvents()->whereIn('type', \App\Models\MatchEvent::GOAL_TYPES)->count();
        $yellowCards = $player->matchEvents()->where('type', \App\Models\MatchEvent::TYPE_YELLOW_CARD)->count();
        $redCards = $player->matchEvents()->where('type', \App\Models\MatchEvent::TYPE_RED_CARD)->count();

        $nextMatch = GameMatch::where('competition_id', $competition->id)
            ->where(fn ($q) => $q->where('home_team_id', $player->team_id)->orWhere('away_team_id', $player->team_id))
            ->where('status', GameMatch::STATUS_SCHEDULED)
            ->orderByRaw('round is null, round asc, id asc')
            ->first();

        $suspension = $nextMatch
            ? $suspensionService->statusForPlayer($player, $nextMatch)
            : ['suspended' => false, 'reason' => null];

        return view('public.competitions.player', compact('competition', 'player', 'goals', 'yellowCards', 'redCards', 'suspension'));
    }

    private function abortIfNotPublished(Competition $competition): void
    {
        abort_unless($competition->isPublished(), 404);
    }
}
