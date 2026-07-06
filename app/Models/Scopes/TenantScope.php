<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Exceptions\TenantContextUnresolvedException;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            throw new TenantContextUnresolvedException(
                sprintf('Cannot query %s without a resolved tenant context.', $model::class),
            );
        }

        $builder->where($model->qualifyColumn('organization_id'), $tenantId);
    }
}
