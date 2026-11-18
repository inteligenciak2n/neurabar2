<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Orders\VenueCustomer;
use App\Support\DeliveryAddressFormatter;
use App\Support\PhoneDigits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueCustomerLookupController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = PhoneDigits::normalize($request->query('phone'));

        if (! PhoneDigits::isValid($phone)) {
            return response()->json(['customer' => null]);
        }

        $customer = VenueCustomer::query()
            ->where('phone', $phone)
            ->with(['addresses' => fn ($query) => $query->latest()])
            ->first();

        if (! $customer) {
            return response()->json(['customer' => null]);
        }

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'addresses' => $customer->addresses->map(fn ($address) => [
                    'id' => $address->id,
                    'zip_code' => DeliveryAddressFormatter::formatZip($address->zip_code),
                    'street' => $address->street,
                    'number' => $address->number,
                    'complement' => $address->complement,
                    'delivery_fee_zone_id' => $address->delivery_fee_zone_id,
                    'neighborhood' => $address->neighborhood,
                    'city' => $address->city,
                    'state' => $address->state,
                    'reference_point' => $address->reference_point,
                    'delivery_phone' => $address->delivery_phone
                        ? DeliveryAddressFormatter::formatPhone($address->delivery_phone)
                        : null,
                    'line' => DeliveryAddressFormatter::line($address),
                ])->values(),
            ],
        ]);
    }
}
