<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\EnsureExhibitorContact;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\RequireTenantContext;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->priority([
            \Illuminate\Auth\Middleware\Authenticate::class,
            ResolveTenant::class,
            RequireTenantContext::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'resolve.tenant' => ResolveTenant::class,
            'tenant.required' => RequireTenantContext::class,
            'org.member' => RequireTenantContext::class,
            'permission' => EnsureUserHasPermission::class,
            'exhibitor.contact' => EnsureExhibitorContact::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ApiExceptionRenderer::register($exceptions);
    })->create();
