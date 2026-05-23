<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetVenueContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->role?->isOperational()) {
            abort(403, 'Platform users cannot access operational routes.');
        }

        $venue = $user->activeVenue();

        if (! $venue) {
            abort(403, 'Venue context unavailable.');
        }

        app()->instance('tenant', $venue);
        $request->merge(['_venue' => $venue]);

        return $next($request);
    }
}
