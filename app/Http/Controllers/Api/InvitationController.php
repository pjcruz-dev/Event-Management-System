<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Invitation\AcceptInvitationAction;
use App\Actions\Invitation\SendInvitationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Policies\InvitationPolicy;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final class InvitationController extends Controller
{
    public function index(Organization $organization): JsonResponse
    {
        $this->ensureCanManageInvitations($organization);

        $invitations = $organization->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(InvitationResource::collection($invitations));
    }

    public function store(
        StoreInvitationRequest $request,
        Organization $organization,
        SendInvitationAction $action,
    ): JsonResponse {
        $this->ensureCanManageInvitations($organization);

        $invitation = $action->handle(
            $organization,
            $request->user(),
            $request->validated('email'),
            $request->validated('role'),
        );

        return ApiResponse::created(
            new InvitationResource($invitation),
            'Invitation sent successfully.',
        );
    }

    public function accept(Request $request, string $token, AcceptInvitationAction $action): JsonResponse
    {
        $invitation = Invitation::query()->where('token', $token)->firstOrFail();

        if ($request->user() === null) {
            return ApiResponse::success([
                'invitation' => new InvitationResource($invitation),
                'requires_registration' => ! User::query()
                    ->where('email', $invitation->email)
                    ->exists(),
            ]);
        }

        $action->handle($invitation, $request->user());

        return ApiResponse::success(message: 'Invitation accepted successfully.');
    }

    public function destroy(Organization $organization, Invitation $invitation): JsonResponse
    {
        $this->authorize('delete', $invitation);

        if ($invitation->organization_id !== $organization->id) {
            abort(404);
        }

        $invitation->delete();

        return ApiResponse::success(message: 'Invitation revoked successfully.');
    }

    public function resend(Organization $organization, Invitation $invitation): JsonResponse
    {
        $this->authorize('resend', $invitation);

        if ($invitation->organization_id !== $organization->id) {
            abort(404);
        }

        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationNotification($invitation->fresh()));

        return ApiResponse::success(
            new InvitationResource($invitation->fresh()),
            'Invitation resent successfully.',
        );
    }

    private function ensureCanManageInvitations(Organization $organization): void
    {
        $user = request()->user();

        if ($user === null || ! app(InvitationPolicy::class)->create($user, $organization)) {
            abort(403, 'This action is unauthorized.');
        }
    }
}
