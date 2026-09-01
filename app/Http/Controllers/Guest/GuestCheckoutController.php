<?php

namespace App\Http\Controllers\Guest;

use App\Actions\Orders\CreateServiceRequestAction;
use App\Enums\ServiceRequestType;
use App\Http\Controllers\Controller;
use App\Models\Orders\Attendance;
use App\Services\GuestTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestCheckoutController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function store(string $token, Request $request, CreateServiceRequestAction $action): JsonResponse
    {
        ['venue' => $venue, 'serviceLocation' => $serviceLocation] = $this->tokenService->decode($token);

        $session = $this->tokenService->resolveSession($request, $venue);

        abort_if($session === null || ! $session->hasPin(), 403, 'No active session.');

        $attendance = $serviceLocation !== null
            ? Attendance::withoutGlobalScopes()
                ->where('venue_id', $venue->id)
                ->where('service_location_id', $serviceLocation->id)
                ->where('status', 'open')
                ->latest()
                ->first()
            : null;

        $action->execute($venue, $serviceLocation, $attendance, ServiceRequestType::Checkout, 'Solicitou fechamento de conta');

        return response()->json(['ok' => true]);
    }
}
