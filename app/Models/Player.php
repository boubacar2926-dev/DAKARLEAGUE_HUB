<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    public const POSITION_GOALKEEPER = 'gardien';

    public const POSITION_DEFENDER = 'defenseur';

    public const POSITION_MIDFIELDER = 'milieu';

    public const POSITION_FORWARD = 'attaquant';

    protected $fillable = [
        'team_id',
        'competition_id',
        'user_id',
        'first_name',
        'last_name',
        'birth_date',
        'position',
        'jersey_number',
        'photo_path',
        'license_number',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'jersey_number' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchEvents(): HasMany
    {
        return $this->hasMany(MatchEvent::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
