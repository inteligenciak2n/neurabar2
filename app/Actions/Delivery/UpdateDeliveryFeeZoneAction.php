<?php

namespace App\Actions\Delivery;

use App\Http\Requests\Delivery\UpdateDeliveryFeeZoneRequest;
use App\Models\Settings\DeliveryFeeZone;

class UpdateDeliveryFeeZoneAction
{
    public function execute(DeliveryFeeZone $zone, UpdateDeliveryFeeZoneRequest $request): DeliveryFeeZone
    {
        $zone->update($request->validated());

        return $zone;
    }
}
