<?php

namespace App\Actions\Delivery;

use App\Models\Settings\DeliveryFeeZone;

class DeleteDeliveryFeeZoneAction
{
    public function execute(DeliveryFeeZone $zone): void
    {
        $zone->delete();
    }
}
