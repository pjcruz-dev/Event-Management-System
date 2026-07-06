<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\EventSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSession extends Model
{
    /** @use HasFactory<EventSessionFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'event_sessions';

    protected $fillable = [
        'organization_id',
        'event_id',
        'track_id',
        'title',
        'description',
        'room',
        'starts_at',
        'ends_at',
        'capacity',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
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

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class, 'event_session_speaker')
            ->using(EventSessionSpeaker::class)
            ->withPivot(['role', 'sort_order'])
            ->withTimestamps();
    }

    public function sessionRegistrations(): HasMany
    {
        return $this->hasMany(SessionRegistration::class);
    }
}
