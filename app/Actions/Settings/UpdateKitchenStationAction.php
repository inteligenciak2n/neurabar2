<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdateKitchenStationRequest;
use App\Models\Settings\KitchenStation;

class UpdateKitchenStationAction
{
    public function execute(KitchenStation $station, UpdateKitchenStationRequest $request): KitchenStation
    {
        $station->update($request->validated());

        return $station->fresh();
    }
}
