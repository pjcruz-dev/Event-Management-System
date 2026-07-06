<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrganizationUser extends Pivot
{
    public $incrementing = true;

    protected $table = 'organization_user';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'status',
        'invited_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
