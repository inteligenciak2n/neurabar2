<?php

namespace App\Http\Controllers\Corporation;

use App\Actions\Corporation\CreateVenueAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Corporation\StoreVenueRequest;
use App\Http\Requests\Corporation\UpdateVenueRequest;
use App\Models\Tenant\Venue;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');
        $venues = Venue::where('corporation_id', $venue->corporation_id)->get();

        return Inertia::render('Corporation/Venues/Index', [
            'venues' => $venues,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Corporation/Venues/Create');
    }

    public function store(StoreVenueRequest $request, CreateVenueAction $action): RedirectResponse
    {
        $venue = app('tenant');
        $corporation = $venue->corporation;

        $action->execute($corporation, $request->validated());

        return redirect()->route('corporation.venues.index')
            ->with('success', 'Venue created successfully.');
    }

    public function edit(Venue $venue): Response
    {
        $currentVenue = app('tenant');
        abort_unless($venue->corporation_id === $currentVenue->corporation_id, 403);

        return Inertia::render('Corporation/Venues/Edit', [
            'venue' => $venue,
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        $currentVenue = app('tenant');
        abort_unless($venue->corporation_id === $currentVenue->corporation_id, 403);

        $venue->update($request->validated());

        return redirect()->route('corporation.venues.index')
            ->with('success', 'Venue updated successfully.');
    }
}
