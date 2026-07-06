<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GuestInviteStatus;
use App\Enums\RsvpResponse;
use App\Traits\BelongsToTenant;
use Database\Factories\GuestInviteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestInvite extends Model
{
    /** @use HasFactory<GuestInviteFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'event_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'invitation_token',
        'status',
        'rsvp_response',
        'household_name',
        'group_label',
        'tags',
        'plus_one_limit',
        'table_id',
        'registration_id',
        'sent_at',
        'opened_at',
        'responded_at',
        'last_reminder_sent_at',
        'reminder_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => GuestInviteStatus::class,
            'rsvp_response' => RsvpResponse::class,
            'tags' => 'array',
            'plus_one_limit' => 'integer',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'responded_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
            'reminder_count' => 'integer',
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

    public function table(): BelongsTo
    {
        return $this->belongsTo(EventTable::class, 'table_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function displayName(): string
    {
        return trim($this->first_name.' '.($this->last_name ?? ''));
    }

    public function isRespondable(): bool
    {
        return ! in_array($this->status, [
            GuestInviteStatus::Revoked,
            GuestInviteStatus::Expired,
        ], true);
    }
}
