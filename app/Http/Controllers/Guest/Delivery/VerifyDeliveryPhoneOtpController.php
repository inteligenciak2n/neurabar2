<?php

namespace App\Http\Controllers\Guest\Delivery;

use App\Actions\Guest\Delivery\VerifyDeliveryPhoneOtpAction;
use App\Enums\ModuleCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\VerifyDeliveryPhoneOtpRequest;
use App\Services\GuestTokenService;
use Illuminate\Http\JsonResponse;

class VerifyDeliveryPhoneOtpController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function store(string $token, VerifyDeliveryPhoneOtpRequest $request, VerifyDeliveryPhoneOtpAction $action): JsonResponse
    {
        ['venue' => $venue] = $this->tokenService->decode($token);

        abort_unless(in_array(ModuleCode::Delivery->value, $venue->activeModules(), true), 404);

        $session = $this->tokenService->resolveSession($request, $venue);

        abort_if($session === null, 403, 'No active session.');

        $verified = $action->execute(
            $session,
            $request->validated('phone'),
            $request->validated('reference_id'),
            $request->validated('code'),
        );

        return response()->json(['verified' => $verified]);
    }
}
