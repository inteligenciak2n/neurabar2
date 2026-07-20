<?php

namespace App\Listeners\Billing;

use App\Enums\ModuleCode;
use App\Events\Orders\GuestSignaled;
use App\Jobs\Billing\RecordModuleUsageJob;

class RecordSignalUsage
{
    public function handle(GuestSignaled $event): void
    {
        $venueId = $event->venueId;

        if (! $venueId) {
            return;
        }

        RecordModuleUsageJob::dispatch($venueId, ModuleCode::DirectWaiter->value);
        RecordModuleUsageJob::dispatch($venueId, ModuleCode::VoiceCommand->value);
    }
}
