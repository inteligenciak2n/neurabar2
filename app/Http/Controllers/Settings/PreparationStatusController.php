<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\CreatePreparationStatusAction;
use App\Actions\Settings\DeletePreparationStatusAction;
use App\Actions\Settings\UpdatePreparationStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StorePreparationStatusRequest;
use App\Http\Requests\Settings\UpdatePreparationStatusRequest;
use App\Models\Settings\PreparationStatus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PreparationStatusController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Settings/PreparationStatuses', [
            'statuses' => $venue->preparationStatuses()->get(),
        ]);
    }

    public function store(StorePreparationStatusRequest $request, CreatePreparationStatusAction $action): RedirectResponse
    {
        $venue = app('tenant');

        $action->execute($venue, $request);

        return back()->with('success', 'Preparation status created.');
    }

    public function update(UpdatePreparationStatusRequest $request, PreparationStatus $status, UpdatePreparationStatusAction $action): RedirectResponse
    {
        $action->execute($status, $request);

        return back()->with('success', 'Preparation status updated.');
    }

    public function destroy(PreparationStatus $status, DeletePreparationStatusAction $action): RedirectResponse
    {
        $action->execute($status);

        return back()->with('success', 'Preparation status deleted.');
    }
}
