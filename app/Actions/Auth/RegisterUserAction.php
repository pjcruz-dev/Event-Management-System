<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Invitation\AcceptInvitationAction;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

final class RegisterUserAction
{
    public function __construct(
        private readonly AcceptInvitationAction $acceptInvitationAction,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, invitation_token?: string|null}  $data
     */
    public function handle(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (! empty($data['invitation_token'])) {
            $invitation = Invitation::query()
                ->where('token', $data['invitation_token'])
                ->firstOrFail();

            $this->acceptInvitationAction->handle($invitation, $user);
        }

        event(new Registered($user));

        return $user;
    }
}
