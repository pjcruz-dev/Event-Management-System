<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantFixtureRequest;
use App\Http\Resources\TenantFixtureResource;
use App\Models\TenantFixture;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class TenantFixtureController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', TenantFixture::class);

        $fixtures = TenantFixture::query()
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            TenantFixtureResource::collection($fixtures),
        );
    }

    public function store(StoreTenantFixtureRequest $request): JsonResponse
    {
        $this->authorize('create', TenantFixture::class);

        $fixture = TenantFixture::query()->create([
            'label' => $request->validated('label'),
        ]);

        return ApiResponse::created(new TenantFixtureResource($fixture));
    }
}
