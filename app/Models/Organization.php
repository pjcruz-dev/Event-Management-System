<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'owner_id',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'status', 'invited_by'])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return array{enabled: bool, description?: string, website_url?: string}
     */
    public function publicProfileSettings(): array
    {
        $settings = $this->settings ?? [];
        $profile = is_array($settings['public_profile'] ?? null) ? $settings['public_profile'] : [];

        return [
            'enabled' => (bool) ($profile['enabled'] ?? false),
            'description' => $profile['description'] ?? null,
            'website_url' => $profile['website_url'] ?? null,
        ];
    }

    public function hasPublicProfile(): bool
    {
        return $this->publicProfileSettings()['enabled'] === true;
    }

    public function defaultCurrency(): string
    {
        $settings = $this->settings ?? [];

        return $settings['default_currency'] ?? 'USD';
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
