<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\StorePreparationStatusRequest;
use App\Models\Settings\PreparationStatus;
use App\Models\Tenant\Venue;

class CreatePreparationStatusAction
{
    public function execute(Venue $venue, StorePreparationStatusRequest $request): PreparationStatus
    {
        return PreparationStatus::create([
            'venue_id' => $venue->id,
            ...$request->validated(),
        ]);
    }
}
