<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdateVenueSettingsRequest;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;
use Illuminate\Support\Arr;

class UpdateVenueSettingsAction
{
    public function execute(Venue $venue, UpdateVenueSettingsRequest $request): VenueSettings
    {
        $validated = $request->validated();

        $settings = VenueSettings::updateOrCreate(
            ['venue_id' => $venue->id],
            Arr::only($validated, ['cover_charge', 'service_fee_percent', 'table_count']),
        );

        $venueAttributes = Arr::only($validated, [
            'require_table',
            'require_tab',
            'require_location',
            'latitude',
            'longitude',
            'require_geolocation',
        ]);

        if ($venueAttributes !== []) {
            $venue->update($venueAttributes);
        }

        return $settings;
    }
}
