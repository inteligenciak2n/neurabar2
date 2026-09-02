<?php

namespace App\Http\Controllers\Guest\Delivery;

use App\Enums\ModuleCode;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Services\GuestTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryCustomerLookupController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function show(string $token, Request $request): JsonResponse
    {
        $request->validate(['phone' => ['required', 'string', 'max:20']]);

        ['venue' => $venue] = $this->tokenService->decode($token);

        abort_unless(in_array(ModuleCode::Delivery->value, $venue->activeModules(), true), 404);

        // Only confirm whether the phone is known; returning the customer's name/address
        // here would leak PII to anyone holding the publicly-shared delivery link.
        $found = Customer::withoutGlobalScopes()
            ->where('corporation_id', $venue->corporation_id)
            ->where('phone', $request->query('phone'))
            ->exists();

        return response()->json(['found' => $found]);
    }

    /**
     * Reveal the saved name/address for a phone, only once its ownership was
     * proven via OTP (GuestSession::isPhoneVerifiedFor) — never before.
     */
    public function reveal(string $token, Request $request): JsonResponse
    {
        $request->validate(['phone' => ['required', 'string', 'max:20']]);

        ['venue' => $venue] = $this->tokenService->decode($token);

        abort_unless(in_array(ModuleCode::Delivery->value, $venue->activeModules(), true), 404);

        $phone = $request->query('phone');
        $session = $this->tokenService->resolveSession($request, $venue);

        abort_unless($session?->isPhoneVerifiedFor($phone), 403, 'Phone not verified.');

        $customer = Customer::withoutGlobalScopes()
            ->where('corporation_id', $venue->corporation_id)
            ->where('phone', $phone)
            ->first();

        abort_if($customer === null, 404);

        $address = $customer->addresses()->orderByDesc('is_default')->latest()->first();

        return response()->json(['name' => $customer->name, 'address' => $address]);
    }
}
