<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Responses\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(message: 'Email is already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return ApiResponse::success(message: 'Email verified successfully.');
    }

    public function resend(Request $request): JsonResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return ApiResponse::success(message: 'Email is already verified.');
        }

        $request->user()?->sendEmailVerificationNotification();

        return ApiResponse::success(message: 'Verification link sent.');
    }
}
