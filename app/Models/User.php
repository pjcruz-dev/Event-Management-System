<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Notifications\ApiVerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
        'phone',
        'timezone',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'status', 'invited_by'])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    public function activeOrganizations(): BelongsToMany
    {
        return $this->organizations()
            ->wherePivot('status', MembershipStatus::Active->value);
    }

    public function isMemberOf(Organization $organization): bool
    {
        return $this->activeOrganizations()
            ->where('organizations.id', $organization->id)
            ->exists();
    }

    public function membershipFor(Organization $organization): ?OrganizationUser
    {
        return $this->memberships()
            ->where('organization_id', $organization->id)
            ->first();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new ApiVerifyEmailNotification);
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

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }
}
