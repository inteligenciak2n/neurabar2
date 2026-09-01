<?php

namespace App\Http\Controllers\Guest\Delivery;

use App\Enums\ModuleCode;
use App\Http\Controllers\Controller;
use App\Models\Settings\DeliveryFeeZone;
use App\Services\GuestTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryFeeZoneLookupController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function show(string $token, Request $request): JsonResponse
    {
        $request->validate(['zip_code' => ['required', 'string']]);

        ['venue' => $venue] = $this->tokenService->decode($token);

        abort_unless(in_array(ModuleCode::Delivery->value, $venue->activeModules(), true), 404);

        $zipCode = (int) preg_replace('/\D/', '', (string) $request->query('zip_code'));

        $zone = DeliveryFeeZone::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->where('zip_code_start', '<=', $zipCode)
            ->where('zip_code_end', '>=', $zipCode)
            ->first();

        if ($zone === null) {
            return response()->json(['message' => 'This address is outside the delivery area.'], 422);
        }

        return response()->json([
            'fee' => (float) $zone->fee,
            'label' => $zone->label,
        ]);
    }
}
