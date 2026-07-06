<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Services\StorageService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return ApiResponse::success(
            new UserResource($user->fresh()),
            'Profile updated successfully.',
        );
    }

    public function uploadAvatar(UploadAvatarRequest $request, StorageService $storage): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_path !== null) {
            $storage->delete($user->avatar_path, 'public');
        }

        $path = $storage->store($request->file('avatar'), 'avatars', 'public');
        $user->update(['avatar_path' => $path]);

        return ApiResponse::success(
            new UserResource($user->fresh()),
            'Avatar uploaded successfully.',
        );
    }
}
