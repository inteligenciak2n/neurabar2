<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $currentRole = $user?->currentVenueRole()?->value;

        if (! in_array($currentRole, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
