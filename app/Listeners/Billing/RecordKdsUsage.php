<?php

namespace App\Listeners\Billing;

use App\Enums\ModuleCode;
use App\Events\Kitchen\ItemStatusUpdated;
use App\Jobs\Billing\RecordModuleUsageJob;

class RecordKdsUsage
{
    public function handle(ItemStatusUpdated $event): void
    {
        $item = $event->item;

        if (! $item->preparationStatus?->is_final) {
            return;
        }

        $venueId = $item->order->attendance?->venue_id;

        if (! $venueId) {
            return;
        }

        RecordModuleUsageJob::dispatch($venueId, ModuleCode::Kds->value);
    }
}
