<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateVenueAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateVenueRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function edit(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Settings/Venue', [
            'venue' => $venue,
        ]);
    }

    public function update(UpdateVenueRequest $request, UpdateVenueAction $action): RedirectResponse
    {
        $venue = app('tenant');

        $action->execute($venue, $request);

        return back()->with('success', 'Venue updated successfully.');
    }
}
