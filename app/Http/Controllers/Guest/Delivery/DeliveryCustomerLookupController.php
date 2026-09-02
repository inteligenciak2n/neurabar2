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
}
