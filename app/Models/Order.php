<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Traits\BelongsToTenant;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'event_id',
        'user_id',
        'registration_id',
        'order_number',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'currency',
        'coupon_id',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'payment_method',
        'paid_at',
        'refund_amount',
        'refunded_at',
        'refund_reason',
        'stripe_refund_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'refund_amount' => 'decimal:2',
            'refunded_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Legacy: the primary registration this order was created for.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * All registrations covered by this order (1 per ticket in a multi-qty purchase).
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(TicketReservation::class);
    }
}
