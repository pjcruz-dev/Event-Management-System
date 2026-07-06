<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $fromRoute = $this->organizationIdFromRoute($request);
        $fromHeader = $this->organizationIdFromHeader($request);
        $organizationId = $fromRoute ?? $fromHeader;

        if ($organizationId === null) {
            return $next($request);
        }

        $organization = Organization::query()->find($organizationId);

        if ($organization === null) {
            abort(404);
        }

        if (! $user->isMemberOf($organization)) {
            if ($fromRoute !== null || $this->routeRequiresTenant($request)) {
                abort(403, 'You are not a member of this organization.');
            }

            return $next($request);
        }

        $this->tenantContext->set($organization);
        $this->permissionRegistrar->setPermissionsTeamId($organization->id);

        return $next($request);
    }

    private function organizationIdFromRoute(Request $request): ?int
    {
        $routeOrganization = $request->route('organization');

        if ($routeOrganization instanceof Organization) {
            return $routeOrganization->id;
        }

        if (is_numeric($routeOrganization)) {
            return (int) $routeOrganization;
        }

        return null;
    }

    private function organizationIdFromHeader(Request $request): ?int
    {
        $header = $request->header('X-Organization-Id');

        if ($header !== null && $header !== '') {
            return (int) $header;
        }

        return null;
    }

    private function routeRequiresTenant(Request $request): bool
    {
        $route = $request->route();

        if ($route === null) {
            return false;
        }

        $middleware = $route->gatherMiddleware();

        return in_array('tenant.required', $middleware, true)
            || in_array('org.member', $middleware, true)
            || in_array(RequireTenantContext::class, $middleware, true);
    }
}
