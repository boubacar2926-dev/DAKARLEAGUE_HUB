<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Competition extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'brouillon';

    public const STATUS_REGISTRATION_OPEN = 'inscriptions_ouvertes';

    public const STATUS_IN_PROGRESS = 'en_cours';

    public const STATUS_FINISHED = 'terminee';

    public const STATUS_ARCHIVED = 'archivee';

    public const FORMAT_SINGLE_ROUND = 'aller_simple';

    public const FORMAT_DOUBLE_ROUND = 'aller_retour';

    public const FORMAT_GROUPS = 'poules';

    public const FORMAT_KNOCKOUT = 'elimination_directe';

    protected $fillable = [
        'name',
        'slug',
        'season',
        'category',
        'description',
        'logo_path',
        'start_date',
        'end_date',
        'format',
        'status',
        'points_win',
        'points_draw',
        'points_loss',
        'tiebreaker_rules',
        'allow_team_managers_to_manage_players',
        'yellow_card_suspension_threshold',
        'red_card_suspension_matches',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'points_win' => 'integer',
            'points_draw' => 'integer',
            'points_loss' => 'integer',
            'tiebreaker_rules' => 'array',
            'allow_team_managers_to_manage_players' => 'boolean',
            'yellow_card_suspension_threshold' => 'integer',
            'red_card_suspension_matches' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Competition $competition) {
            if (empty($competition->slug)) {
                $competition->slug = static::uniqueSlug($competition->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'competition_organizer');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function scopePublished($query)
    {
        return $query->whereIn('status', [
            self::STATUS_REGISTRATION_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_FINISHED,
            self::STATUS_ARCHIVED,
        ]);
    }

    public function isOrganizedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->created_by === $user->id
            || $this->organizers()->where('users.id', $user->id)->exists();
    }

    public function isPublished(): bool
    {
        return in_array($this->status, [
            self::STATUS_REGISTRATION_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_FINISHED,
            self::STATUS_ARCHIVED,
        ], true);
    }
}
