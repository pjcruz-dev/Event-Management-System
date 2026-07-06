<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['activeOrganizations']);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'organizations' => OrganizationResource::collection($user->activeOrganizations),
        ]);
    }
}
