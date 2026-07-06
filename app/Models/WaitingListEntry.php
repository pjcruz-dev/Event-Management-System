<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WaitingListStatus;
use App\Traits\BelongsToTenant;
use Database\Factories\WaitingListEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitingListEntry extends Model
{
    /** @use HasFactory<WaitingListEntryFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'event_id',
        'ticket_type_id',
        'user_id',
        'attendee_first_name',
        'attendee_last_name',
        'attendee_email',
        'attendee_phone',
        'custom_fields',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'status' => WaitingListStatus::class,
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

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
