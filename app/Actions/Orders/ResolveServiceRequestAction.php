<?php

namespace App\Actions\Orders;

use App\Enums\ServiceRequestStatus;
use App\Events\Orders\ServiceRequestUpdated;
use App\Models\Orders\ServiceRequest;
use App\Models\User;

class ResolveServiceRequestAction
{
    public function execute(ServiceRequest $serviceRequest, User $user): ServiceRequest
    {
        $serviceRequest->update([
            'status' => ServiceRequestStatus::Resolved,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);

        event(new ServiceRequestUpdated($serviceRequest));

        return $serviceRequest;
    }
}
