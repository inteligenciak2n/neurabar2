<?php

namespace App\Http\Controllers\Guest;

use App\Actions\Orders\CreateServiceRequestAction;
use App\Enums\ModuleCode;
use App\Enums\ServiceRequestType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\StoreGuestSignalRequest;
use App\Models\Orders\Attendance;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;
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
        $activeModules = $venue->activeModules();

        return Inertia::render('Guest/Hub', [
            'token' => $token,
            'venue' => $venue->only('id', 'name', 'logo_url', 'require_geolocation'),
            'serviceLocation' => $serviceLocation?->only('id', 'name', 'type'),
            'attendanceChannel' => $attendanceChannel?->only('id', 'name'),
            'hasSession' => $session !== null && $session->hasPin(),
            'geolocationVerified' => $session?->geolocation_verified ?? false,
            'hasSelfOrder' => in_array(ModuleCode::SelfOrder->value, $activeModules, true),
            'hasDirectWaiter' => in_array(ModuleCode::DirectWaiter->value, $activeModules, true),
            'hasTaker' => in_array(ModuleCode::Taker->value, $activeModules, true),
        ]);
    }

    public function signal(string $token, StoreGuestSignalRequest $request, CreateServiceRequestAction $action): JsonResponse
    {
        ['venue' => $venue, 'serviceLocation' => $serviceLocation] = $this->tokenService->decode($token);

        abort_unless(in_array(ModuleCode::DirectWaiter->value, $venue->activeModules(), true), 404);

        $session = $this->tokenService->resolveSession($request, $venue);

        abort_if($session === null, 403, 'No active session.');

        $validated = $request->validated();

        $action->execute(
            $venue,
            $serviceLocation,
            $this->resolveOpenAttendance($venue, $serviceLocation),
            ServiceRequestType::Message,
            $validated['message'] ?? null,
        );

        return response()->json(['ok' => true]);
    }

    public function requestOrderAssistance(string $token, Request $request, CreateServiceRequestAction $action): JsonResponse
    {
        ['venue' => $venue, 'serviceLocation' => $serviceLocation] = $this->tokenService->decode($token);

        abort_unless(in_array(ModuleCode::Taker->value, $venue->activeModules(), true), 404);

        $session = $this->tokenService->resolveSession($request, $venue);

        abort_if($session === null, 403, 'No active session.');

        $action->execute(
            $venue,
            $serviceLocation,
            $this->resolveOpenAttendance($venue, $serviceLocation),
            ServiceRequestType::CallToOrder,
            null,
        );

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

    /**
     * Resolve the open Attendance for this service location, if any, so the
     * request can snapshot the attendant currently responsible for the table.
     */
    private function resolveOpenAttendance(Venue $venue, ?ServiceLocation $serviceLocation): ?Attendance
    {
        if ($serviceLocation === null) {
            return null;
        }

        return Attendance::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('service_location_id', $serviceLocation->id)
            ->where('status', 'open')
            ->latest()
            ->first();
    }
}
