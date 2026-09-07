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

    /**
     * Plafond d'effectif par équipe et par compétition — usage courant en championnat amateur
     * pour éviter les listes de joueurs disproportionnées (aucune exigence du cahier des charges,
     * mais une taille illimitée n'a pas de sens administratif).
     */
    public const MAX_ROSTER_SIZE = 30;

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

    public function positionLabel(): ?string
    {
        return match ($this->position) {
            self::POSITION_GOALKEEPER => 'Gardien',
            self::POSITION_DEFENDER => 'Défenseur',
            self::POSITION_MIDFIELDER => 'Milieu',
            self::POSITION_FORWARD => 'Attaquant',
            default => null,
        };
    }

    /**
     * Ordre d'affichage "feuille de match" (gardien puis défenseurs, milieux, attaquants).
     */
    public function positionOrder(): int
    {
        return match ($this->position) {
            self::POSITION_GOALKEEPER => 0,
            self::POSITION_DEFENDER => 1,
            self::POSITION_MIDFIELDER => 2,
            self::POSITION_FORWARD => 3,
            default => 4,
        };
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }
}
