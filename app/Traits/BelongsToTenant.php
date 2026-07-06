<?php

declare(strict_types=1);

namespace App\Traits;

use App\Exceptions\TenantContextUnresolvedException;
use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('organization_id') !== null) {
                return;
            }

            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                throw new TenantContextUnresolvedException(
                    sprintf('Cannot create %s without a resolved tenant context.', static::class),
                );
            }

            $model->setAttribute('organization_id', $tenantId);
        });
    }

    public static function withoutTenantScope(?string $reason = null): Builder
    {
        Log::channel('security')->warning('Tenant scope bypassed', [
            'model' => static::class,
            'user_id' => auth()->id(),
            'reason' => $reason ?? 'unspecified',
        ]);

        return static::withoutGlobalScope(TenantScope::class);
    }
}
