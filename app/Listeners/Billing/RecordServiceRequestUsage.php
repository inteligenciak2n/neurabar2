<?php

namespace App\Listeners\Billing;

use App\Enums\ModuleCode;
use App\Enums\ServiceRequestType;
use App\Events\Orders\ServiceRequestCreated;
use App\Jobs\Billing\RecordModuleUsageJob;

class RecordServiceRequestUsage
{
    public function handle(ServiceRequestCreated $event): void
    {
        $serviceRequest = $event->serviceRequest;

        if ($serviceRequest->type !== ServiceRequestType::Message) {
            return;
        }

        RecordModuleUsageJob::dispatch($serviceRequest->venue_id, ModuleCode::DirectWaiter->value);
    }
}
