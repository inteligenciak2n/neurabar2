<?php

namespace App\Http\Middleware;

use App\Services\Billing\BillingStatusService;
use App\Services\GuestTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia o fluxo público de QR code quando a assinatura da venue está
 * suspensa ou cancelada.
 *
 * O bloqueio por inadimplência só existia nas rotas operacionais autenticadas:
 * uma venue suspensa continuava recebendo pedidos de clientes finais, ou seja,
 * a plataforma seguia entregando o serviço que deixou de ser pago.
 */
class EnsureBillingActive
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        if (! is_string($token) || $token === '') {
            return $next($request);
        }

        ['venue' => $venue] = $this->tokenService->decode($token);

        abort_if(
            BillingStatusService::isSuspended($venue),
            503,
            'Este estabelecimento está temporariamente indisponível.'
        );

        return $next($request);
    }
}
