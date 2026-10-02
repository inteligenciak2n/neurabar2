<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdateVenueRequest;
use App\Models\Tenant\Venue;

class UpdateVenueAction
{
    public function __construct(private readonly StoreVenueLogoAction $storeLogo) {}

    public function execute(Venue $venue, UpdateVenueRequest $request): Venue
    {
        $data = $request->safe()->except(['logo', 'remove_logo']);

        if ($request->hasFile('logo')) {
            $data['logo_url'] = $this->storeLogo->execute($request->file('logo'), $venue);
        } elseif ($request->boolean('remove_logo')) {
            $this->storeLogo->deleteStored($venue);
            $data['logo_url'] = null;
        }

        $venue->update($data);

        return $venue->fresh();
    }
}
