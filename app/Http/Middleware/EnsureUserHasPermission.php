<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserHasPermission
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $organization = $this->tenantContext->get();

        if ($organization === null) {
            abort(422, 'Organization context is required before checking permissions.');
        }

        $this->permissionRegistrar->setPermissionsTeamId($organization->id);

        if (! $user->hasPermissionTo($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
