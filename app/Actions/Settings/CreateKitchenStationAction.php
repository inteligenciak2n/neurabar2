<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\StoreKitchenStationRequest;
use App\Models\Settings\KitchenStation;
use App\Models\Tenant\Venue;

class CreateKitchenStationAction
{
    public function execute(Venue $venue, StoreKitchenStationRequest $request): KitchenStation
    {
        return KitchenStation::create([
            'venue_id' => $venue->id,
            ...$request->validated(),
        ]);
    }
}
