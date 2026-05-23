<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\CreateServiceLocationAction;
use App\Actions\Settings\DeleteServiceLocationAction;
use App\Actions\Settings\UpdateServiceLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreServiceLocationRequest;
use App\Http\Requests\Settings\UpdateServiceLocationRequest;
use App\Models\Settings\ServiceLocation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceLocationController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Settings/ServiceLocations', [
            'locations' => app('tenant')->serviceLocations()->get(),
        ]);
    }

    public function store(StoreServiceLocationRequest $request, CreateServiceLocationAction $action): RedirectResponse
    {
        $venue = app('tenant');

        $action->execute($venue, $request);

        return back()->with('success', 'Service location created.');
    }

    public function update(UpdateServiceLocationRequest $request, ServiceLocation $location, UpdateServiceLocationAction $action): RedirectResponse
    {
        $action->execute($location, $request);

        return back()->with('success', 'Service location updated.');
    }

    public function destroy(ServiceLocation $location, DeleteServiceLocationAction $action): RedirectResponse
    {
        $action->execute($location);

        return back()->with('success', 'Service location deleted.');
    }
}
