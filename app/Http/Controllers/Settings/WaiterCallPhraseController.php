<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateWaiterCallPhrasesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateWaiterCallPhrasesRequest;
use App\Models\Settings\VenueSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WaiterCallPhraseController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Settings/WaiterCallPhrases', [
            'phrases' => VenueSettings::phrasesForVenue($venue->id),
        ]);
    }

    public function update(UpdateWaiterCallPhrasesRequest $request, UpdateWaiterCallPhrasesAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request);

        return back()->with('success', 'Waiter call phrases updated.');
    }
}
