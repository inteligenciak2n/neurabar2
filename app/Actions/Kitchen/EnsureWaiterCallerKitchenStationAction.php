<?php

namespace App\Actions\Kitchen;

use App\Enums\KitchenStationCode;
use App\Models\Settings\KitchenStation;
use App\Models\Tenant\Venue;

class EnsureWaiterCallerKitchenStationAction
{
    public function execute(Venue $venue): KitchenStation
    {
        $code = KitchenStationCode::WaiterCaller;

        $station = KitchenStation::withoutGlobalScopes()->firstOrCreate(
            [
                'venue_id' => $venue->id,
                'code' => $code,
            ],
            [
                'name' => $code->defaultName(),
                'sort_order' => 0,
                'active' => true,
            ],
        );

        if (! $station->active) {
            $station->forceFill(['active' => true])->save();
        }

        return $station->fresh() ?? $station;
    }
}
