<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SendInvitationAction
{
    public function handle(
        Organization $organization,
        User $inviter,
        string $email,
        string $role,
    ): Invitation {
        $email = strtolower(trim($email));

        if ($organization->members()
            ->where('users.email', $email)
            ->wherePivot('status', 'active')
            ->exists()) {
            throw ValidationException::withMessages([
                'email' => ['This user is already a member of the organization.'],
            ]);
        }

        $existing = $organization->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'email' => ['A pending invitation already exists for this email.'],
            ]);
        }

        $invitation = $organization->invitations()->create([
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'invited_by' => $inviter->id,
        ]);

        Notification::route('mail', $email)
            ->notify(new InvitationNotification($invitation));

        return $invitation;
    }
}
