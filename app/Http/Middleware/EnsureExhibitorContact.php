<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ExhibitorContact;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureExhibitorContact
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof ExhibitorContact) {
            abort(403, 'Exhibitor portal access required.');
        }

        return $next($request);
    }
}
