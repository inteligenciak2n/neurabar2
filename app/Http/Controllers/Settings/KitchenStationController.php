<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\CreateKitchenStationAction;
use App\Actions\Settings\DeleteKitchenStationAction;
use App\Actions\Settings\UpdateKitchenStationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreKitchenStationRequest;
use App\Http\Requests\Settings\UpdateKitchenStationRequest;
use App\Models\Settings\KitchenStation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class KitchenStationController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Settings/KitchenStations', [
            'stations' => $venue->kitchenStations()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreKitchenStationRequest $request, CreateKitchenStationAction $action): RedirectResponse
    {
        $venue = app('tenant');

        $action->execute($venue, $request);

        return back()->with('success', 'Kitchen station created.');
    }

    public function update(UpdateKitchenStationRequest $request, KitchenStation $station, UpdateKitchenStationAction $action): RedirectResponse
    {
        $action->execute($station, $request);

        return back()->with('success', 'Kitchen station updated.');
    }

    public function destroy(KitchenStation $station, DeleteKitchenStationAction $action): RedirectResponse
    {
        $action->execute($station);

        return back()->with('success', 'Kitchen station deleted.');
    }
}
