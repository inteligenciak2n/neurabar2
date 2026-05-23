<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdateServiceLocationRequest;
use App\Models\Settings\ServiceLocation;

class UpdateServiceLocationAction
{
    public function execute(ServiceLocation $location, UpdateServiceLocationRequest $request): ServiceLocation
    {
        $location->update($request->validated());

        return $location->fresh();
    }
}
