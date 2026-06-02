<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TenantConnectionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetVenueContext
{
    public function __construct(private readonly TenantConnectionResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $venue = $user->currentVenue;

        if (! $venue) {
            return redirect()->route('no-venue.index');
        }

        $hasAccess = $user->venues()->wherePivot('venue_id', $venue->id)->exists();

        if (! $hasAccess) {
            abort(403, 'Acesso não autorizado a esta venue.');
        }

        // Carrega a corporation com eager loading para evitar N+1 no resolver
        $venue->load('corporation');

        // Resolve a conexão operacional e registra no container para o request corrente
        $connectionName = $this->resolver->resolve($venue);
        $isDedicated = (bool) $venue->corporation?->is_dedicated;

        app()->instance('tenant', $venue);
        app()->instance('operational_connection', $connectionName);
        app()->instance('operational_is_dedicated', $isDedicated);

        $request->merge(['_venue' => $venue]);

        return $next($request);
    }
}
