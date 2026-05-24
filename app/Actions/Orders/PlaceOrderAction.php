<?php

namespace App\Actions\Orders;

use App\Events\Orders\OrderPlaced;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrderAction
{
    public function execute(Attendance $attendance, StoreOrderRequest $request): Order
    {
        if ($attendance->status !== 'open') {
            throw ValidationException::withMessages([
                'attendance' => 'Cannot place order on a closed attendance.',
            ]);
        }

        $order = DB::transaction(function () use ($attendance, $request): Order {
            $orderNumber = Order::where('attendance_id', $attendance->id)->max('order_number') + 1;

            $order = Order::create([
                'attendance_id' => $attendance->id,
                'order_number' => $orderNumber,
                'status' => 'open',
                'created_by' => auth()->id(),
            ]);

            foreach ($request->validated()['items'] as $itemData) {
                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                foreach ($itemData['modifiers'] ?? [] as $modifierData) {
                    $item->modifiers()->create([
                        'modifier_option_id' => $modifierData['modifier_option_id'],
                    ]);
                }
            }

            return $order;
        });

        event(new OrderPlaced($order));

        return $order;
    }
}
