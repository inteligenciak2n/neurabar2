<?php

namespace App\Http\Controllers\Guest;

use App\Events\Orders\GuestSignaled;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\StoreGuestSignalRequest;
use App\Services\GeolocationService;
use App\Services\GuestTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestHubController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function show(string $token, Request $request): Response
    {
        ['venue' => $venue, 'serviceLocation' => $serviceLocation, 'attendanceChannel' => $attendanceChannel] = $this->tokenService->decode($token);

        $session = $this->tokenService->resolveSession($request, $venue);

        return Inertia::render('Guest/Hub', [
            'token' => $token,
            'venue' => $venue->only('id', 'name', 'logo_url', 'require_geolocation'),
            'serviceLocation' => $serviceLocation?->only('id', 'name', 'type'),
            'attendanceChannel' => $attendanceChannel?->only('id', 'name'),
            'hasSession' => $session !== null && $session->hasPin(),
            'geolocationVerified' => $session?->geolocation_verified ?? false,
        ]);
    }

    public function signal(string $token, StoreGuestSignalRequest $request): JsonResponse
    {
        ['venue' => $venue, 'serviceLocation' => $serviceLocation] = $this->tokenService->decode($token);

        $session = $this->tokenService->resolveSession($request, $venue);

        abort_if($session === null, 403, 'No active session.');

        $validated = $request->validated();

        $locationName = $serviceLocation?->name ?? 'Visitante';

        event(new GuestSignaled(
            venueId: $venue->id,
            locationName: $locationName,
            message: $validated['message'] ?? null,
            signalOnly: (bool) ($validated['signal_only'] ?? false),
        ));

        return response()->json(['ok' => true]);
    }

    public function verifyLocation(string $token, Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        ['venue' => $venue] = $this->tokenService->decode($token);

        $session = $this->tokenService->resolveSession($request, $venue);

        abort_if($session === null, 403, 'No active session.');

        $service = app(GeolocationService::class);

        if ($venue->latitude === null || $venue->longitude === null) {
            return response()->json(['allowed' => true, 'distance' => null]);
        }

        $distance = (int) $service->distanceInMeters(
            (float) $request->lat,
            (float) $request->lng,
            (float) $venue->latitude,
            (float) $venue->longitude,
        );

        $allowed = $service->isWithinRange($venue, (float) $request->lat, (float) $request->lng);

        if ($allowed) {
            $session->update(['geolocation_verified' => true]);
        }

        return response()->json(['allowed' => $allowed, 'distance' => $distance]);
    }
}
