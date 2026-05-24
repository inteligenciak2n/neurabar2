<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use Inertia\Inertia;
use Inertia\Response;

class TrackOrderController extends Controller
{
    public function show(Order $order): Response
    {
        $items = $order->items()
            ->with(['product:id,name', 'variation:id,name', 'preparationStatus:id,name,color,show_to_customer'])
            ->get()
            ->filter(fn ($item) => $item->preparationStatus?->show_to_customer)
            ->values()
            ->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product?->name,
                'variation_name' => $item->variation?->name,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
                'status' => [
                    'name' => $item->preparationStatus?->name,
                    'color' => $item->preparationStatus?->color,
                ],
                'ready_at' => $item->ready_at?->toISOString(),
            ]);

        return Inertia::render('Guest/TrackOrder', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'items' => $items,
            ],
        ]);
    }
}
