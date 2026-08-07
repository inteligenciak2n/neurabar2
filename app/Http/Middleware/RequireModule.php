<?php

namespace App\Http\Middleware;

use App\Enums\ModuleAccessReason;
use App\Models\Tenant\Venue;
use App\Services\Billing\ModuleAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RequireModule
{
    public function __construct(private readonly ModuleAccessService $moduleAccess) {}

    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        $venue = app('tenant');
        $result = $this->moduleAccess->check($venue instanceof Venue ? $venue : null, $moduleCode);

        if ($result->allowed) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $result->message(),
                'reason' => $result->reason->value,
            ], 403);
        }

        // Inadimplência tem tela própria — e é justamente onde o cliente
        // consegue pagar. Abortar com 403 aqui era o que produzia o lockout.
        if ($result->reason === ModuleAccessReason::BillingBlocked) {
            return redirect()
                ->route('settings.subscription.index')
                ->with('billing_blocked', $result->message());
        }

        // Módulo não contratado/ativo é o momento de maior intenção de compra:
        // servimos um paywall com upsell em vez de um 403 cru.
        if ($result->reason->isUpsellOpportunity()) {
            return Inertia::render('Errors/ModuleLocked', [
                'access' => $result->toArray(),
                'canManageSubscription' => Gate::allows('manage-subscription'),
            ])->toResponse($request)->setStatusCode(403);
        }

        abort(403, $result->message());
    }
}
