<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Throwaway model for Phase 2 tenancy isolation tests only.
 * Not a business domain model — removed or repurposed when real models land in Phase 3.
 */
final class TenantFixture extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'organization_id',
        'label',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
