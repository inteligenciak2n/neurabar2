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

        // created_by é nulo tanto no self-order quanto no delivery/retirada (ambos anônimos);
        // a existência de um DeliveryOrder na attendance é o que diferencia os dois.
        $order->loadMissing('attendance.deliveryOrder');

        $moduleCode = match (true) {
            $order->created_by !== null => ModuleCode::Taker,
            $order->attendance->deliveryOrder !== null => ModuleCode::Delivery,
            default => ModuleCode::SelfOrder,
        };

        RecordModuleUsageJob::dispatch($venueId, $moduleCode->value);
        RecordModuleUsageJob::dispatch($venueId, ModuleCode::DirectPrint->value);
    }
}
