<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Enums\MembershipStatus;
use App\Models\Invitation;
use App\Models\User;
use App\Services\OrganizationRoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcceptInvitationAction
{
    public function __construct(
        private readonly OrganizationRoleService $organizationRoleService,
    ) {}

    public function handle(Invitation $invitation, User $user): void
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation has already been accepted.'],
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation has expired.'],
            ]);
        }

        if (strtolower($invitation->email) !== strtolower($user->email)) {
            throw ValidationException::withMessages([
                'email' => ['This invitation was sent to a different email address.'],
            ]);
        }

        DB::transaction(function () use ($invitation, $user): void {
            $organization = $invitation->organization;

            if ($user->membershipFor($organization) === null) {
                $organization->members()->attach($user->id, [
                    'role' => $invitation->role,
                    'status' => MembershipStatus::Active->value,
                    'invited_by' => $invitation->invited_by,
                ]);

                $this->organizationRoleService->assignRole(
                    $user,
                    $organization,
                    $invitation->role,
                );
            }

            $invitation->update(['accepted_at' => now()]);
        });
    }
}
