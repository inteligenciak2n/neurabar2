<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateVenueSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateVenueSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VenueSettingsController extends Controller
{
    public function edit(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Settings/General', [
            'settings' => $venue->settings,
        ]);
    }

    public function update(UpdateVenueSettingsRequest $request, UpdateVenueSettingsAction $action): RedirectResponse
    {
        $venue = app('tenant');

        $action->execute($venue, $request);

        return back()->with('success', 'Settings updated successfully.');
    }
}
