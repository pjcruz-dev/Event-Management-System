<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Organization\CreateOrganizationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreCustomRoleRequest;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateMemberRoleRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationMemberResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\RoleResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizations = $request->user()
            ->activeOrganizations()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            OrganizationResource::collection($organizations),
        );
    }

    public function store(
        StoreOrganizationRequest $request,
        CreateOrganizationAction $action,
    ): JsonResponse {
        $organization = $action->handle(
            $request->user(),
            $request->validated('name'),
            $request->validated('slug'),
        );

        return ApiResponse::created(
            new OrganizationResource($organization),
            'Organization created successfully.',
        );
    }

    public function show(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        return ApiResponse::success(new OrganizationResource($organization));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);
        $organization->update($request->validated());

        return ApiResponse::success(
            new OrganizationResource($organization->fresh()),
            'Organization updated successfully.',
        );
    }

    public function members(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        $members = $organization->members()
            ->wherePivot('status', 'active')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            OrganizationMemberResource::collection($members),
        );
    }

    public function updateMemberRole(
        UpdateMemberRoleRequest $request,
        Organization $organization,
        User $user,
        OrganizationRoleService $roleService,
    ): JsonResponse {
        $this->authorize('manageMembers', $organization);

        if (! $user->isMemberOf($organization)) {
            abort(404);
        }

        $role = $request->validated('role');
        $roleService->assignRole($user, $organization, $role);

        $organization->members()->updateExistingPivot($user->id, [
            'role' => $role,
        ]);

        return ApiResponse::success(message: 'Member role updated successfully.');
    }

    public function roles(Organization $organization, OrganizationRoleService $roleService): JsonResponse
    {
        $this->authorize('view', $organization);

        return ApiResponse::success(
            RoleResource::collection($roleService->rolesForOrganization($organization)),
        );
    }

    public function storeRole(
        StoreCustomRoleRequest $request,
        Organization $organization,
        OrganizationRoleService $roleService,
    ): JsonResponse {
        $this->authorize('manageRoles', $organization);

        $role = $roleService->createCustomRole(
            $organization,
            $request->validated('name'),
            $request->validated('permissions'),
        );

        return ApiResponse::created(
            new RoleResource($role->load('permissions')),
            'Custom role created successfully.',
        );
    }
}
