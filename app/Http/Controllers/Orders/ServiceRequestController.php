<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\AcknowledgeServiceRequestAction;
use App\Actions\Orders\ResolveServiceRequestAction;
use App\Models\Orders\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceRequestController
{
    public function acknowledge(Request $request, ServiceRequest $serviceRequest, AcknowledgeServiceRequestAction $action): RedirectResponse
    {
        $action->execute($serviceRequest, $request->user());

        return back();
    }

    public function resolve(Request $request, ServiceRequest $serviceRequest, ResolveServiceRequestAction $action): RedirectResponse
    {
        $action->execute($serviceRequest, $request->user());

        return back();
    }
}
