<?php

declare(strict_types=1);

namespace App\Actions\Organization;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateOrganizationAction
{
    public function __construct(
        private readonly OrganizationRoleService $organizationRoleService,
    ) {}

    public function handle(User $user, string $name, ?string $slug = null): Organization
    {
        return DB::transaction(function () use ($user, $name, $slug): Organization {
            $slug = $this->resolveUniqueSlug($slug ?? Str::slug($name));

            $organization = Organization::query()->create([
                'name' => $name,
                'slug' => $slug,
                'owner_id' => $user->id,
                'settings' => [],
            ]);

            $this->organizationRoleService->createBaselineRoles($organization);

            $organization->members()->attach($user->id, [
                'role' => 'owner',
                'status' => MembershipStatus::Active->value,
            ]);

            $this->organizationRoleService->assignRole($user, $organization, 'owner');

            return $organization->fresh(['owner']);
        });
    }

    private function resolveUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
