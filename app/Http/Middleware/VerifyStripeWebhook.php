<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyStripeWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('Stripe-Signature')) {
            return response()->json(['error' => 'Missing Stripe-Signature header.'], 400);
        }

        return $next($request);
    }
}
