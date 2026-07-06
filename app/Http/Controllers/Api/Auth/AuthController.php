<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Responses\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $user = $action->handle($request->validated());
        $token = $user->createToken($request->input('device_name', 'web'))->plainTextToken;

        return ApiResponse::created([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Registration successful. Please verify your email address.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken($request->validated('device_name'))->plainTextToken;

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $plainTextToken = $request->bearerToken();

        if ($plainTextToken !== null) {
            PersonalAccessToken::findToken($plainTextToken)?->delete();
        }

        return ApiResponse::success(message: 'Logged out successfully.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return ApiResponse::success(
            message: 'If an account exists for that email, a password reset link has been sent.',
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return ApiResponse::success(message: 'Password has been reset successfully.');
    }
}
