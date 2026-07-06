<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class OrganizationRoleService
{
    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function createBaselineRoles(Organization $organization): void
    {
        $this->permissionRegistrar->setPermissionsTeamId($organization->id);

        $roleMap = config('roles', []);
        $allPermissions = Permission::query()->pluck('name')->all();

        foreach ($roleMap as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'organization_id' => $organization->id,
            ]);

            if ($permissions === '*') {
                $role->syncPermissions($allPermissions);

                continue;
            }

            $role->syncPermissions($permissions);
        }
    }

    public function createCustomRole(Organization $organization, string $name, array $permissions): Role
    {
        $this->permissionRegistrar->setPermissionsTeamId($organization->id);

        $role = Role::query()->create([
            'name' => $name,
            'guard_name' => 'web',
            'organization_id' => $organization->id,
        ]);

        $role->syncPermissions($permissions);

        return $role;
    }

    public function assignRole(User $user, Organization $organization, string $roleName): void
    {
        $this->permissionRegistrar->setPermissionsTeamId($organization->id);
        $user->syncRoles([$roleName]);
    }

    public function rolesForOrganization(Organization $organization): Collection
    {
        $this->permissionRegistrar->setPermissionsTeamId($organization->id);

        return Role::query()
            ->where('organization_id', $organization->id)
            ->with('permissions')
            ->orderBy('name')
            ->get();
    }

    public function userHasPermission(User $user, Organization $organization, string $permission): bool
    {
        $this->permissionRegistrar->setPermissionsTeamId($organization->id);

        return $user->hasPermissionTo($permission);
    }
}
