<?php

namespace App\Http\Controllers\Guest;

use App\Events\Orders\GuestSignaled;
use App\Http\Controllers\Controller;
use App\Services\GuestTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestCheckoutController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function store(string $token, Request $request): JsonResponse
    {
        ['venue' => $venue, 'serviceLocation' => $serviceLocation] = $this->tokenService->decode($token);

        $session = $this->tokenService->resolveSession($request, $venue);

        abort_if($session === null || ! $session->hasPin(), 403, 'No active session.');

        $locationName = $serviceLocation?->name ?? 'Visitante';

        event(new GuestSignaled(
            venueId: $venue->id,
            locationName: $locationName,
            message: 'Solicitou fechamento de conta',
            signalOnly: false,
        ));

        return response()->json(['ok' => true]);
    }
}
