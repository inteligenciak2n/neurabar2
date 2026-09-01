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

        $customer = Customer::withoutGlobalScopes()
            ->where('corporation_id', $venue->corporation_id)
            ->where('phone', $request->query('phone'))
            ->with(['addresses' => fn ($q) => $q->latest()])
            ->first();

        if ($customer === null) {
            return response()->json(['customer' => null]);
        }

        return response()->json([
            'customer' => [
                'name' => $customer->name,
                'phone' => $customer->phone,
                'addresses' => $customer->addresses->map(fn ($address) => [
                    'id' => $address->id,
                    'label' => $address->label,
                    'street' => $address->street,
                    'number' => $address->number,
                    'complement' => $address->complement,
                    'neighborhood' => $address->neighborhood,
                    'city' => $address->city,
                    'state' => $address->state,
                    'zip_code' => $address->zip_code,
                    'reference_point' => $address->reference_point,
                ]),
            ],
        ]);
    }
}
