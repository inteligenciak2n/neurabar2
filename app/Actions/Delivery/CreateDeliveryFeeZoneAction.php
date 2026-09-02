<?php

namespace App\Actions\Delivery;

use App\Models\Settings\DeliveryFeeZone;
use App\Models\Tenant\Venue;

class CreateDeliveryFeeZoneAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Venue $venue, array $data): DeliveryFeeZone
    {
        return DeliveryFeeZone::create([
            'venue_id' => $venue->id,
            ...$data,
        ]);
    }
}
