<?php

namespace App\Actions\Orders;

use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Events\Orders\ServiceRequestCreated;
use App\Models\Orders\Attendance;
use App\Models\Orders\ServiceRequest;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;

class CreateServiceRequestAction
{
    public function execute(
        Venue $venue,
        ?ServiceLocation $serviceLocation,
        ?Attendance $attendance,
        ServiceRequestType $type,
        ?string $message,
    ): ServiceRequest {
        $serviceRequest = ServiceRequest::withoutGlobalScopes()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $serviceLocation?->id,
            'attendance_id' => $attendance?->id,
            // Snapshot de quem abriu o atendimento: pode ser nulo quando a Attendance
            // foi auto-criada pelo próprio fluxo guest (sem staff ter aberto antes).
            'assigned_user_id' => $attendance?->created_by,
            'type' => $type,
            'message' => $message,
            'status' => ServiceRequestStatus::Pending,
        ]);

        event(new ServiceRequestCreated($serviceRequest));

        return $serviceRequest;
    }
}
