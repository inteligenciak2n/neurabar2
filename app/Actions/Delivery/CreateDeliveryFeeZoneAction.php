<?php

namespace App\Actions\Delivery;

use App\Http\Requests\Delivery\StoreDeliveryFeeZoneRequest;
use App\Models\Settings\DeliveryFeeZone;
use App\Models\Tenant\Venue;

class CreateDeliveryFeeZoneAction
{
    public function execute(Venue $venue, StoreDeliveryFeeZoneRequest $request): DeliveryFeeZone
    {
        return DeliveryFeeZone::create([
            'venue_id' => $venue->id,
            ...$request->validated(),
        ]);
    }
}
