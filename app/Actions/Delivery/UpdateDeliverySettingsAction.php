<?php

namespace App\Actions\Delivery;

use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;

class UpdateDeliverySettingsAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Venue $venue, array $data): VenueSettings
    {
        return VenueSettings::updateOrCreate(
            ['venue_id' => $venue->id],
            $data
        );
    }
}
