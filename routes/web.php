<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CompetitionController as AdminCompetitionController;
use App\Http\Controllers\Admin\ConvocationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\LineupController;
use App\Http\Controllers\Admin\MatchController as AdminMatchController;
use App\Http\Controllers\Admin\MatchResultController;
use App\Http\Controllers\Admin\PlayerController as AdminPlayerController;
use App\Http\Controllers\Admin\TeamController as AdminTeamController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\CompetitionController as PublicCompetitionController;
use App\Http\Controllers\Public\TeamRegistrationController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pages publiques (§2.4-F du cahier des charges — consultables sans compte)
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicCompetitionController::class, 'index'])->name('home');

Route::prefix('competitions')->name('public.competitions.')->group(function () {
    Route::get('/', [PublicCompetitionController::class, 'index'])->name('index');
    Route::get('/{competition:slug}', [PublicCompetitionController::class, 'show'])->name('show');
    Route::get('/{competition:slug}/classement', [PublicCompetitionController::class, 'standings'])->name('standings');
    Route::get('/{competition:slug}/statistiques', [PublicCompetitionController::class, 'stats'])->name('stats');
    Route::get('/{competition:slug}/calendrier', [PublicCompetitionController::class, 'calendar'])->name('calendar');
    Route::get('/{competition:slug}/resultats', [PublicCompetitionController::class, 'results'])->name('results');
    Route::get('/{competition:slug}/equipes/{team}', [PublicCompetitionController::class, 'team'])->name('team');
    Route::get('/{competition:slug}/joueurs/{player}', [PublicCompetitionController::class, 'player'])->name('player');

    Route::middleware('auth')->group(function () {
        Route::get('/{competition:slug}/inscription', [TeamRegistrationController::class, 'create'])->name('register-team');
        Route::post('/{competition:slug}/inscription', [TeamRegistrationController::class, 'store'])->name('register-team.store');
    });
});

/*
|--------------------------------------------------------------------------
| Tableau de bord (redirige vers l'espace organisateur selon le rôle)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    if ($user->isSuperAdmin() || $user->isOrganisateur()) {
        return redirect()->route('admin.dashboard');
    }

    return app(DashboardController::class)->index($request);
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Espace organisateur / super-admin (§2.4-G, matrice des droits §2.3)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:'.User::ROLE_SUPER_ADMIN.','.User::ROLE_ORGANISATEUR.','.User::ROLE_RESPONSABLE])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('journal', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::resource('competitions', AdminCompetitionController::class);

        Route::resource('competitions.teams', AdminTeamController::class)
            ->shallow()
            ->except(['show']);

        Route::patch('teams/{team}/valider', [AdminTeamController::class, 'approve'])->name('teams.approve');
        Route::patch('teams/{team}/refuser', [AdminTeamController::class, 'reject'])->name('teams.reject');

        Route::resource('teams.players', AdminPlayerController::class)
            ->shallow()
            ->except(['show']);

        Route::resource('competitions.matches', AdminMatchController::class)
            ->shallow()
            ->except(['show']);

        Route::post('competitions/{competition}/matches/generate', [AdminMatchController::class, 'generate'])
            ->name('competitions.matches.generate');

        Route::post('competitions/{competition}/matches/generate-next-round', [AdminMatchController::class, 'generateNextRound'])
            ->name('competitions.matches.generate-next-round');

        Route::get('matches/{match}/resultat', [MatchResultController::class, 'edit'])->name('matches.result.edit');
        Route::put('matches/{match}/resultat', [MatchResultController::class, 'update'])->name('matches.result.update');

        Route::get('matches/{match}/convocation', [ConvocationController::class, 'edit'])->name('matches.convocation.edit');
        Route::put('matches/{match}/convocation', [ConvocationController::class, 'update'])->name('matches.convocation.update');

        Route::get('matches/{match}/composition', [LineupController::class, 'edit'])->name('matches.lineup.edit');
        Route::put('matches/{match}/composition', [LineupController::class, 'update'])->name('matches.lineup.update');

        Route::get('competitions/{competition}/export/calendrier', [ExportController::class, 'calendar'])->name('competitions.export.calendar');
        Route::get('competitions/{competition}/export/classement', [ExportController::class, 'standings'])->name('competitions.export.standings');
    });

require __DIR__.'/auth.php';
