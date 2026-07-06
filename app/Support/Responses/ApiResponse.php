<?php

declare(strict_types=1);

namespace App\Support\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => null,
        ], $status);
    }

    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    public static function error(
        ?string $message = null,
        mixed $errors = null,
        int $status = 400,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, ?string $message = null): JsonResponse
    {
        return self::success([
            'items' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], $message);
    }
}
