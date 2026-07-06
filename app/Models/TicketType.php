<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketTypeVisibility;
use App\Traits\BelongsToTenant;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    /** @use HasFactory<TicketTypeFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'event_id',
        'name',
        'type_tag',
        'description',
        'price',
        'currency',
        'quantity',
        'quantity_sold',
        'per_order_limit',
        'sales_starts_at',
        'sales_ends_at',
        'visibility',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'visibility' => TicketTypeVisibility::class,
            'quantity' => 'integer',
            'quantity_sold' => 'integer',
            'per_order_limit' => 'integer',
            'sales_starts_at' => 'datetime',
            'sales_ends_at' => 'datetime',
            'is_active' => 'boolean',
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

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(TicketReservation::class);
    }

    public function waitingListEntries(): HasMany
    {
        return $this->hasMany(WaitingListEntry::class);
    }
}
