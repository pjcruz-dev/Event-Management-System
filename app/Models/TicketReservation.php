<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketReservationStatus;
use App\Traits\BelongsToTenant;
use Database\Factories\TicketReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReservation extends Model
{
    /** @use HasFactory<TicketReservationFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'event_id',
        'ticket_type_id',
        'order_id',
        'quantity',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => TicketReservationStatus::class,
            'expires_at' => 'datetime',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
