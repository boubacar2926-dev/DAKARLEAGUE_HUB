<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'competition_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Journalise une action sensible (RG10 : traçabilité des modifications).
     */
    public static function record(string $action, Model $subject, string $description, array $properties = [], ?int $competitionId = null): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'competition_id' => $competitionId,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}
