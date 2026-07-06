<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;

abstract class BelongsToTenantPolicy
{
    protected function userCanAccessTenantResource(User $user, Model $model): bool
    {
        $organizationId = $model->getAttribute('organization_id');

        if ($organizationId === null) {
            return false;
        }

        $contextId = app(TenantContext::class)->id();

        if ($contextId === null || (int) $organizationId !== $contextId) {
            return false;
        }

        $organization = Organization::query()->find($organizationId);

        if ($organization === null) {
            return false;
        }

        return $user->isMemberOf($organization);
    }
}
