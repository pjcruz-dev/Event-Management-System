<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\SessionRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionRegistration extends Model
{
    /** @use HasFactory<SessionRegistrationFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'event_id',
        'event_session_id',
        'registration_id',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
