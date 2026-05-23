<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdateVenueSettingsRequest;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;

class UpdateVenueSettingsAction
{
    public function execute(Venue $venue, UpdateVenueSettingsRequest $request): VenueSettings
    {
        return VenueSettings::updateOrCreate(
            ['venue_id' => $venue->id],
            $request->validated()
        );
    }
}
