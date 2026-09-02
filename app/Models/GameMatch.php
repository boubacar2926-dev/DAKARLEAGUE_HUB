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
}
