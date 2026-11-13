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

        $settingsAttributes = Arr::only($validated, ['cover_charge', 'service_fee_percent', 'table_count']);

        $venueAttributes = Arr::only($validated, [
            'require_table',
            'require_tab',
            'require_location',
            'latitude',
            'longitude',
            'require_geolocation',
        ]);

        // Venue (saas) and VenueSettings (operational) live on different connections, so they can't share
        // a transaction; the Venue write goes first as it's the one that can fail on validation-level data.
        if ($venueAttributes !== []) {
            $venue->update($venueAttributes);
        }

        $settings = $settingsAttributes === []
            ? VenueSettings::firstOrNew(['venue_id' => $venue->id])
            : VenueSettings::updateOrCreate(['venue_id' => $venue->id], $settingsAttributes);

        return $settings;
    }
}
