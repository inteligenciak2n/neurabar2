<?php

namespace App\Listeners\Kitchen;

use App\Events\Kitchen\NewOrderReceived;
use App\Events\Orders\OrderPlaced;
use App\Models\Settings\KitchenStation;

class BroadcastNewOrderByStation
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load('items.product', 'attendance');

        $itemsByStation = $order->items
            ->filter(fn ($item) => $item->product !== null)
            ->groupBy(fn ($item) => $item->product->kitchen_station_id);

        foreach ($itemsByStation as $stationId => $stationItems) {
            if (empty($stationId)) {
                continue;
            }

            $station = KitchenStation::withoutGlobalScopes()->find($stationId);

            if ($station === null) {
                continue;
            }

            $itemsPayload = $stationItems->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
                'unit_price' => $item->unit_price,
            ])->values()->all();

            event(new NewOrderReceived($order, $station, $itemsPayload));
        }
    }
}
