<?php

namespace App\Http\Middleware;

use App\Services\GuestTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetGuestOperationalConnection
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        abort_unless(is_string($token), 404);

        $this->tokenService->decode($token);

        return $next($request);
    }
}
