<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEvent extends Model
{
    use HasFactory;

    public const TYPE_GOAL = 'but';

    public const TYPE_PENALTY_GOAL = 'but_penalty';

    public const TYPE_OWN_GOAL = 'but_contre_son_camp';

    public const TYPE_YELLOW_CARD = 'carton_jaune';

    public const TYPE_RED_CARD = 'carton_rouge';

    public const GOAL_TYPES = [
        self::TYPE_GOAL,
        self::TYPE_PENALTY_GOAL,
        self::TYPE_OWN_GOAL,
    ];

    protected $fillable = [
        'match_id',
        'team_id',
        'player_id',
        'type',
        'minute',
    ];

    protected function casts(): array
    {
        return [
            'minute' => 'integer',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
