<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    public const TYPE_CALENDAR_PDF = 'calendrier_pdf';

    public const TYPE_STANDINGS_PDF = 'classement_pdf';

    public const TYPE_REPORT_PDF = 'rapport_pdf';

    public const TYPE_ATTACHMENT = 'piece_jointe';

    protected $fillable = [
        'competition_id',
        'uploaded_by',
        'type',
        'title',
        'file_path',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
