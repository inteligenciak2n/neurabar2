<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\StoreGuestSessionRequest;
use App\Http\Requests\Guest\VerifyGuestPinRequest;
use App\Services\GuestTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class GuestSessionController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function store(string $token, StoreGuestSessionRequest $request): JsonResponse
    {
        ['venue' => $venue, 'serviceLocation' => $serviceLocation, 'attendanceChannel' => $attendanceChannel] = $this->tokenService->decode($token);

        $existingSession = $this->tokenService->resolveSession($request, $venue);

        if ($existingSession !== null) {
            return response()->json(['already_exists' => true]);
        }

        $session = $this->tokenService->createSession(
            venue: $venue,
            serviceLocation: $serviceLocation,
            attendanceChannel: $attendanceChannel,
            pin: $request->validated('pin'),
        );

        return response()
            ->json(['session_id' => $session->id])
            ->withCookie(cookie(
                name: 'guest_token',
                value: $session->guest_token,
                minutes: 60 * 24,
                path: '/',
                secure: app()->isProduction(),
                httpOnly: true,
                sameSite: 'Strict',
            ));
    }

    public function verify(string $token, VerifyGuestPinRequest $request): JsonResponse
    {
        ['venue' => $venue] = $this->tokenService->decode($token);

        $session = $this->tokenService->resolveSession($request, $venue);

        abort_if($session === null, 403, 'No active session.');

        $valid = Hash::check($request->validated('pin'), $session->pin);

        return response()->json(['valid' => $valid]);
    }
}
