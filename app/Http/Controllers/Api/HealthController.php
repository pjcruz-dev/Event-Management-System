<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class HealthController extends Controller
{
    public function show(): JsonResponse
    {
        return ApiResponse::success([
            'status' => 'ok',
            'service' => config('app.name'),
        ]);
    }
}
