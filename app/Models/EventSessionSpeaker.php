<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SessionSpeakerRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventSessionSpeaker extends Pivot
{
    protected $table = 'event_session_speaker';

    protected $fillable = [
        'event_session_id',
        'speaker_id',
        'role',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'role' => SessionSpeakerRole::class,
            'sort_order' => 'integer',
        ];
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    public function speaker(): BelongsTo
    {
        return $this->belongsTo(Speaker::class);
    }
}
