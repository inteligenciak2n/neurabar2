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

        // created_by é nulo quando o pedido veio do próprio visitante (self-order);
        // quando um usuário staff lança o pedido (Taker), created_by é o seu id.
        $moduleCode = $order->created_by !== null ? ModuleCode::Taker : ModuleCode::SelfOrder;

        RecordModuleUsageJob::dispatch($venueId, $moduleCode->value);
        RecordModuleUsageJob::dispatch($venueId, ModuleCode::DirectPrint->value);
    }
}
