<?php

namespace App\Http\Controllers\Guest\Delivery;

use App\Actions\Guest\Delivery\RequestDeliveryPhoneOtpAction;
use App\Enums\ModuleCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\RequestDeliveryPhoneOtpRequest;
use App\Services\GuestTokenService;
use Illuminate\Http\JsonResponse;

class RequestDeliveryPhoneOtpController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function store(string $token, RequestDeliveryPhoneOtpRequest $request, RequestDeliveryPhoneOtpAction $action): JsonResponse
    {
        ['venue' => $venue] = $this->tokenService->decode($token);

        abort_unless(in_array(ModuleCode::Delivery->value, $venue->activeModules(), true), 404);

        $result = $action->execute($venue, $request->validated('phone'));

        $session = $this->tokenService->resolveSession($request, $venue)
            ?? $this->tokenService->createSession($venue, null, null);

        return response()
            ->json(['reference_id' => $result['reference_id']])
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
}
