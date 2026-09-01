<?php

namespace App\Actions\Delivery;

use App\Http\Requests\Delivery\UpdateDeliverySettingsRequest;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;

class UpdateDeliverySettingsAction
{
    public function execute(Venue $venue, UpdateDeliverySettingsRequest $request): VenueSettings
    {
        return VenueSettings::updateOrCreate(
            ['venue_id' => $venue->id],
            $request->validated()
        );
    }
}
