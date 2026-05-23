<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdateVenueRequest;
use App\Models\Tenant\Venue;

class UpdateVenueAction
{
    public function execute(Venue $venue, UpdateVenueRequest $request): Venue
    {
        $venue->update($request->validated());

        return $venue->fresh();
    }
}
