<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Enums\RsvpResponse;
use App\Traits\BelongsToTenant;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'event_id',
        'user_id',
        'ticket_type_id',
        'order_id',
        'registration_number',
        'status',
        'rsvp_response',
        'guest_invite_id',
        'is_plus_one',
        'primary_registration_id',
        'table_id',
        'attendee_first_name',
        'attendee_last_name',
        'attendee_email',
        'attendee_phone',
        'custom_fields',
        'qr_token_hash',
        'qr_token_encrypted',
        'checked_in_at',
        'checked_in_by',
        'check_in_device_id',
        'check_in_gate',
        'check_in_latitude',
        'check_in_longitude',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'rsvp_response' => RsvpResponse::class,
            'custom_fields' => 'array',
            'is_plus_one' => 'boolean',
            'checked_in_at' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
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

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function guestInvite(): BelongsTo
    {
        return $this->belongsTo(GuestInvite::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(EventTable::class, 'table_id');
    }

    public function primaryRegistration(): BelongsTo
    {
        return $this->belongsTo(self::class, 'primary_registration_id');
    }

    public function plusOnes(): HasMany
    {
        return $this->hasMany(self::class, 'primary_registration_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Legacy inverse: finds the order that references this registration via orders.registration_id.
     */
    public function legacyOrder(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }
}
