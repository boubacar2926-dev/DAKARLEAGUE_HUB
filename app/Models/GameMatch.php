<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    public const STATUS_SCHEDULED = 'programme';

    public const STATUS_IN_PROGRESS = 'en_cours';

    public const STATUS_FINISHED = 'termine';

    public const STATUS_POSTPONED = 'reporte';

    public const STATUS_CANCELLED = 'annule';

    protected $fillable = [
        'competition_id',
        'home_team_id',
        'away_team_id',
        'round',
        'scheduled_at',
        'venue',
        'status',
        'home_score',
        'away_score',
        'home_penalties',
        'away_penalties',
        'postponed_reason',
        'cancellation_reason',
        'validated_at',
        'validated_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'validated_at' => 'datetime',
            'home_score' => 'integer',
            'away_score' => 'integer',
            'home_penalties' => 'integer',
            'away_penalties' => 'integer',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class, 'match_id');
    }

    public function lineups(): HasMany
    {
        return $this->hasMany(Lineup::class, 'match_id');
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    /**
     * "Exempté" (bye) : une équipe qualifiée d'office faute d'adversaire au tirage au sort
     * d'un tableau à élimination directe dont le nombre d'équipes n'est pas une puissance de 2.
     */
    public function isBye(): bool
    {
        return $this->away_team_id === null;
    }

    /**
     * Équipe gagnante d'un match terminé — nécessaire pour faire progresser un tableau à
     * élimination directe. Un score nul n'est tranché que par une séance de tirs au but
     * (home_penalties/away_penalties) : sans elle, aucun vainqueur ne peut être désigné.
     */
    public function winnerTeamId(): ?int
    {
        if ($this->isBye()) {
            return $this->isFinished() ? $this->home_team_id : null;
        }

        if (! $this->isFinished() || $this->home_score === null || $this->away_score === null) {
            return null;
        }

        if ($this->home_score > $this->away_score) {
            return $this->home_team_id;
        }

        if ($this->away_score > $this->home_score) {
            return $this->away_team_id;
        }

        if ($this->home_penalties !== null && $this->away_penalties !== null && $this->home_penalties !== $this->away_penalties) {
            return $this->home_penalties > $this->away_penalties ? $this->home_team_id : $this->away_team_id;
        }

        return null;
    }
}
