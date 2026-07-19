<?php

namespace App\Listeners\Billing;

use App\Enums\ModuleCode;
use App\Events\Orders\OrderPlaced;
use App\Jobs\Billing\RecordModuleUsageJob;

class RecordOrderModuleUsage
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $venueId = $order->attendance?->venue_id;

        if (! $venueId) {
            return;
        }

        RecordModuleUsageJob::dispatch($venueId, ModuleCode::Kds->value);
        RecordModuleUsageJob::dispatch($venueId, ModuleCode::Taker->value);
        RecordModuleUsageJob::dispatch($venueId, ModuleCode::DirectPrint->value);
    }
}
