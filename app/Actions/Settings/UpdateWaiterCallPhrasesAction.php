<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdateWaiterCallPhrasesRequest;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;

class UpdateWaiterCallPhrasesAction
{
    public function execute(Venue $venue, UpdateWaiterCallPhrasesRequest $request): VenueSettings
    {
        return VenueSettings::updateOrCreate(
            ['venue_id' => $venue->id],
            ['waiter_call_phrases' => VenueSettings::normalizePhrases($request->validated('phrases'))],
        );
    }
}
