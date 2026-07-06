<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\TenantContextUnresolvedException;
use App\Support\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ApiExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $exception->getMessage() ?: 'The given data was invalid.',
                $exception->errors(),
                422,
            );
        });

        $exceptions->render(function (TenantContextUnresolvedException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $exception->getMessage(),
                null,
                422,
            );
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $exception->getMessage() ?: 'This action is unauthorized.',
                null,
                403,
            );
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Resource not found.', null, 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Resource not found.', null, 404);
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException
                || $exception instanceof AuthorizationException
                || $exception instanceof ModelNotFoundException
                || $exception instanceof NotFoundHttpException
                || $exception instanceof TenantContextUnresolvedException) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            $message = config('app.debug')
                ? $exception->getMessage()
                : 'An unexpected error occurred.';

            $errors = config('app.debug')
                ? [
                    'exception' => $exception::class,
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]
                : null;

            return ApiResponse::error($message, $errors, $status);
        });
    }
}
