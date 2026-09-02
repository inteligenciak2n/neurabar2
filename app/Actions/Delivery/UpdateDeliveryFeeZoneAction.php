<?php

namespace App\Actions\Delivery;

use App\Models\Settings\DeliveryFeeZone;

class UpdateDeliveryFeeZoneAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(DeliveryFeeZone $zone, array $data): DeliveryFeeZone
    {
        $zone->update($data);

        return $zone;
    }
}
