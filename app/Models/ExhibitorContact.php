<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\ExhibitorContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ExhibitorContact extends Authenticatable
{
    /** @use HasFactory<ExhibitorContactFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'organization_id',
        'event_id',
        'exhibitor_id',
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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

    public function exhibitor(): BelongsTo
    {
        return $this->belongsTo(Exhibitor::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(ExhibitorLead::class, 'scanned_by');
    }
}
