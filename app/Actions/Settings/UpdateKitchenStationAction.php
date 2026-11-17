<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdateKitchenStationRequest;
use App\Models\Settings\KitchenStation;

class UpdateKitchenStationAction
{
    public function execute(KitchenStation $station, UpdateKitchenStationRequest $request): KitchenStation
    {
        $payload = $request->validated();

        if ($station->isWaiterCaller()) {
            $payload['active'] = true;
        }

        $station->update($payload);

        return $station->fresh();
    }
}
