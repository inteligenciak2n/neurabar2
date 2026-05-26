<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetVenueContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::findOrFail($request->user()->id);

        if (! $user) {
            return $next($request);
        }

        if (! $user->role?->isOperational()) {
            abort(403, 'Platform users cannot access operational routes.');
        }

        $venue = $user->activeVenue();

        if (! $venue) {
            dd('teste', $user->activeVenue());
            abort(403, 'Venue context unavailable.');
        }

        app()->instance('tenant', $venue);
        $request->merge(['_venue' => $venue]);

        return $next($request);
    }
}
