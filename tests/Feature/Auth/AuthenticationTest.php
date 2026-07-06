<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Organizer',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name' => 'test',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'Password123!',
            'device_name' => 'test',
        ]);

        $login->assertOk()->assertJsonStructure(['data' => ['token']]);
        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'login@example.com');

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_can_verify_email_via_signed_url(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHour(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->getJson($url)->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
