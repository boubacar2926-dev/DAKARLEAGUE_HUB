<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    public const REGISTRATION_PENDING = 'en_attente';

    public const REGISTRATION_APPROVED = 'validee';

    public const REGISTRATION_REJECTED = 'refusee';

    protected $fillable = [
        'competition_id',
        'manager_user_id',
        'name',
        'logo_path',
        'primary_color',
        'secondary_color',
        'city',
        'home_ground',
        'manager_name',
        'contact_phone',
        'contact_email',
        'registration_status',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'away_team_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('registration_status', self::REGISTRATION_APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->registration_status === self::REGISTRATION_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->registration_status === self::REGISTRATION_PENDING;
    }
}
