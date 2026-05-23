<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\StoreServiceLocationRequest;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;

class CreateServiceLocationAction
{
    public function execute(Venue $venue, StoreServiceLocationRequest $request): ServiceLocation
    {
        return ServiceLocation::create([
            'venue_id' => $venue->id,
            ...$request->validated(),
        ]);
    }
}
