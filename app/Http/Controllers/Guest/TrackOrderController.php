<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Services\GuestTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TrackOrderController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function show(string $orderId, Request $request): Response
    {
        abort_unless(Str::isUuid($orderId), 404);

        $venue = null;
        $token = $request->query('token');

        if ($token !== null) {
            abort_unless(is_string($token), 404);

            ['venue' => $venue] = $this->tokenService->decode($token);
        }

        $orderQuery = Order::query()->whereKey($orderId);

        if ($venue !== null) {
            $orderQuery->whereHas('attendance', fn ($query) => $query->where('venue_id', $venue->id));
        }

        $order = $orderQuery->firstOrFail();
        $order->load('attendance.attendanceChannel:id,is_trackable', 'attendance.deliveryOrder');

        abort_if(! ($order->attendance?->attendanceChannel?->is_trackable ?? true), 404);

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

        $deliveryOrder = $order->attendance?->deliveryOrder;

        return Inertia::render('Guest/TrackOrder', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'code' => $deliveryOrder?->code ?? $order->order_number,
                'status' => $order->status,
                'items' => $items,
                'fulfillment_type' => $deliveryOrder?->fulfillment_type,
            ],
        ]);
    }
}
