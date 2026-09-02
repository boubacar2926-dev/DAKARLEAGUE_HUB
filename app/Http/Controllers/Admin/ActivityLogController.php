<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Competition;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Journal des actions sensibles (RG10) : création, modification de score,
     * report, annulation, validation. Un organisateur ne voit que ses compétitions.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $competitions = $user->isSuperAdmin()
            ? Competition::orderBy('name')->get()
            : Competition::where(fn ($q) => $q->where('created_by', $user->id)
                ->orWhereHas('organizers', fn ($q) => $q->where('users.id', $user->id)))
                ->orderBy('name')
                ->get();

        $query = ActivityLog::with(['user', 'competition'])->latest();

        if (! $user->isSuperAdmin()) {
            $query->whereIn('competition_id', $competitions->pluck('id'));
        }

        if ($request->filled('competition_id')) {
            $query->where('competition_id', $request->integer('competition_id'));
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.activity-logs.index', compact('logs', 'competitions'));
    }
}
