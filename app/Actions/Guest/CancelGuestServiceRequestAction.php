<?php

namespace App\Actions\Guest;

use App\Enums\ServiceRequestStatus;
use App\Events\Orders\ServiceRequestUpdated;
use App\Models\Orders\ServiceRequest;

class CancelGuestServiceRequestAction
{
    public function execute(ServiceRequest $serviceRequest): ServiceRequest
    {
        $serviceRequest->update([
            'status' => ServiceRequestStatus::Resolved,
            'resolved_at' => now(),
        ]);

        event(new ServiceRequestUpdated($serviceRequest));

        return $serviceRequest;
    }
}
