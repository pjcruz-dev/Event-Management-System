<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponDiscountType;
use App\Traits\BelongsToTenant;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'event_id',
        'code',
        'discount_type',
        'discount_value',
        'applicable_ticket_type_ids',
        'max_uses',
        'times_used',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => CouponDiscountType::class,
            'discount_value' => 'decimal:2',
            'applicable_ticket_type_ids' => 'array',
            'max_uses' => 'integer',
            'times_used' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
