<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\SpeakerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Speaker extends Model
{
    /** @use HasFactory<SpeakerFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'event_id',
        'name',
        'title',
        'bio',
        'photo_path',
        'social_links',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventSessions(): BelongsToMany
    {
        return $this->belongsToMany(EventSession::class, 'event_session_speaker')
            ->using(EventSessionSpeaker::class)
            ->withPivot(['role', 'sort_order'])
            ->withTimestamps();
    }
}
