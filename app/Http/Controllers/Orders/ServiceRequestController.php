<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\AcknowledgeServiceRequestAction;
use App\Actions\Orders\ClaimAttendanceAction;
use App\Actions\Orders\ReleaseAttendanceAction;
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

    public function assign(Request $request, ServiceRequest $serviceRequest, ClaimAttendanceAction $action): RedirectResponse
    {
        abort_if($serviceRequest->attendance_id === null, 422, 'This request has no attendance to claim.');

        $action->execute($serviceRequest->attendance, $request->user());

        return back();
    }

    public function release(Request $request, ServiceRequest $serviceRequest, ReleaseAttendanceAction $action): RedirectResponse
    {
        abort_if($serviceRequest->attendance_id === null, 422, 'This request has no attendance to release.');

        $action->execute($serviceRequest->attendance, $request->user());

        return back();
    }
}
