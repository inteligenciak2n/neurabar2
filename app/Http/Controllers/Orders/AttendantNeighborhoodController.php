<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\RegisterAttendantNeighborhoodAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreAttendantNeighborhoodRequest;
use App\Models\Settings\DeliveryFeeZone;
use Illuminate\Http\JsonResponse;

class AttendantNeighborhoodController extends Controller
{
    public function store(StoreAttendantNeighborhoodRequest $request, RegisterAttendantNeighborhoodAction $action): JsonResponse
    {
        $zone = $action->execute(app('tenant'), $request->validated());

        return response()->json([
            'neighborhood' => $this->payload($zone),
        ]);
    }

    /**
     * @return array{id: string, name: string, fee: float, has_label: bool}
     */
    private function payload(DeliveryFeeZone $zone): array
    {
        return [
            'id' => $zone->id,
            'name' => (string) $zone->label,
            'fee' => (float) $zone->fee,
            'has_label' => filled($zone->label),
        ];
    }
}
