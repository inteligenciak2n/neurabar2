<?php

namespace App\Http\Controllers\Delivery;

use App\Actions\Delivery\CreateDeliveryFeeZoneAction;
use App\Actions\Delivery\DeleteDeliveryFeeZoneAction;
use App\Actions\Delivery\UpdateDeliveryFeeZoneAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Delivery\DeliveryFeeZoneRequest;
use App\Models\Settings\DeliveryFeeZone;
use Illuminate\Http\RedirectResponse;

class FeeZoneController extends Controller
{
    public function store(DeliveryFeeZoneRequest $request, CreateDeliveryFeeZoneAction $action): RedirectResponse
    {
        $venue = app('tenant');

        $action->execute($venue, $request->validated());

        return back()->with('success', 'Delivery fee zone created.');
    }

    public function update(DeliveryFeeZoneRequest $request, DeliveryFeeZone $feeZone, UpdateDeliveryFeeZoneAction $action): RedirectResponse
    {
        $action->execute($feeZone, $request->validated());

        return back()->with('success', 'Delivery fee zone updated.');
    }

    public function destroy(DeliveryFeeZone $feeZone, DeleteDeliveryFeeZoneAction $action): RedirectResponse
    {
        $action->execute($feeZone);

        return back()->with('success', 'Delivery fee zone deleted.');
    }
}
