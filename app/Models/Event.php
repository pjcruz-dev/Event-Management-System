<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomDomainVerificationStatus;
use App\Enums\EventCategory;
use App\Enums\EventRegistrationMode;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Traits\BelongsToTenant;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'venue',
        'timezone',
        'capacity',
        'status',
        'visibility',
        'registration_mode',
        'rsvp_settings',
        'confirmation_settings',
        'category',
        'theme_config',
        'landing_page_config',
        'custom_domain',
        'custom_domain_verification_status',
        'meta_title',
        'meta_description',
        'og_image_path',
        'starts_at',
        'ends_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'visibility' => EventVisibility::class,
            'registration_mode' => EventRegistrationMode::class,
            'rsvp_settings' => 'array',
            'confirmation_settings' => 'array',
            'category' => EventCategory::class,
            'custom_domain_verification_status' => CustomDomainVerificationStatus::class,
            'theme_config' => 'array',
            'landing_page_config' => 'array',
            'capacity' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(EventSetting::class);
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    public function eventSessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(Speaker::class);
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }

    public function exhibitors(): HasMany
    {
        return $this->hasMany(Exhibitor::class);
    }

    public function booths(): HasMany
    {
        return $this->hasMany(Booth::class);
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function registrationForm(): HasOne
    {
        return $this->hasOne(RegistrationForm::class);
    }

    public function waitingListEntries(): HasMany
    {
        return $this->hasMany(WaitingListEntry::class);
    }

    public function guestInvites(): HasMany
    {
        return $this->hasMany(GuestInvite::class);
    }

    public function previewTokens(): HasMany
    {
        return $this->hasMany(EventPreviewToken::class);
    }

    public function eventTables(): HasMany
    {
        return $this->hasMany(EventTable::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedRsvpSettings(): array
    {
        $defaults = [
            'allow_plus_ones' => false,
            'max_plus_ones_per_invite' => 0,
            'collect_meal_preferences' => false,
            'meal_options' => ['Chicken', 'Fish', 'Vegetarian', 'Vegan'],
            'allow_maybe_response' => true,
            'response_deadline' => null,
            'auto_send_reminders' => false,
            'reminder_days_before_deadline' => 3,
        ];

        return array_merge($defaults, $this->rsvp_settings ?? []);
    }

    /**
     * @return array<string, string|null>
     */
    public function resolvedConfirmationSettings(): array
    {
        $defaults = [
            'rsvp_accepted_message' => null,
            'rsvp_declined_message' => null,
            'rsvp_maybe_message' => null,
            'registration_pending_message' => null,
            'registration_confirmed_message' => null,
        ];

        return array_merge($defaults, $this->confirmation_settings ?? []);
    }

    public function requiresInvitationToken(): bool
    {
        return in_array($this->registration_mode, [
            EventRegistrationMode::InviteOnly,
            EventRegistrationMode::Rsvp,
        ], true);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
