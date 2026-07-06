<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventTableShape;
use App\Traits\BelongsToTenant;
use Database\Factories\EventTableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTable extends Model
{
    /** @use HasFactory<EventTableFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'event_id',
        'name',
        'capacity',
        'sort_order',
        'shape',
        'x',
        'y',
        'rotation',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'sort_order' => 'integer',
            'shape' => EventTableShape::class,
            'x' => 'float',
            'y' => 'float',
            'rotation' => 'float',
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

    public function guestInvites(): HasMany
    {
        return $this->hasMany(GuestInvite::class, 'table_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'table_id');
    }

    public function assignedCount(): int
    {
        return $this->registrations()->count()
            + $this->guestInvites()->whereNull('registration_id')->count();
    }
}
