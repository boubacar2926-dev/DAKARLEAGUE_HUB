<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ORGANISATEUR = 'organisateur';

    public const ROLE_RESPONSABLE = 'responsable';

    public const ROLE_JOUEUR = 'joueur';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * 'role' et 'is_active' sont volontairement exclus : ce sont des champs sensibles qui ne
     * doivent jamais pouvoir être définis par mass assignment depuis une entrée utilisateur.
     * Ils se modifient uniquement via une affectation directe explicite (ex. $user->role = ...).
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isOrganisateur(): bool
    {
        return $this->role === self::ROLE_ORGANISATEUR;
    }

    public function isResponsable(): bool
    {
        return $this->role === self::ROLE_RESPONSABLE;
    }

    public function isJoueur(): bool
    {
        return $this->role === self::ROLE_JOUEUR;
    }

    /**
     * Compétitions créées par cet utilisateur.
     */
    public function createdCompetitions(): HasMany
    {
        return $this->hasMany(Competition::class, 'created_by');
    }

    /**
     * Compétitions que cet utilisateur est autorisé à organiser.
     */
    public function organizedCompetitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class, 'competition_organizer');
    }

    /**
     * Équipes dont cet utilisateur est responsable.
     */
    public function managedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'manager_user_id');
    }

    /**
     * Profil(s) joueur associé(s) à ce compte.
     */
    public function playerProfiles(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
