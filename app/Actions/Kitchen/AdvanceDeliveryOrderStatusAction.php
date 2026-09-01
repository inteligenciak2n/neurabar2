<?php

namespace App\Actions\Kitchen;

use App\Actions\Orders\CloseAttendanceAction;
use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Events\Kitchen\OrderStatusUpdated;
use App\Models\Orders\Order;
use Illuminate\Validation\ValidationException;

class AdvanceDeliveryOrderStatusAction
{
    public function __construct(private readonly CloseAttendanceAction $closeAttendanceAction) {}

    public function execute(Order $order): Order
    {
        $order->loadMissing('attendance.deliveryOrder');

        $deliveryOrder = $order->attendance?->deliveryOrder;

        if ($deliveryOrder === null) {
            throw ValidationException::withMessages([
                'order' => 'This order is not a delivery/pickup order.',
            ]);
        }

        $nextStatus = match ($order->status) {
            OrderStatus::Ready => $deliveryOrder->fulfillment_type === FulfillmentType::Delivery
                ? OrderStatus::OutForDelivery
                : OrderStatus::Delivered,
            OrderStatus::OutForDelivery => OrderStatus::Delivered,
            default => throw ValidationException::withMessages([
                'order' => 'Order is not ready to advance to the next delivery status.',
            ]),
        };

        $order->update(['status' => $nextStatus]);

        if ($nextStatus === OrderStatus::Delivered) {
            $this->closeAttendanceAction->execute($order->attendance);
        }

        event(new OrderStatusUpdated($order->fresh('attendance')));

        return $order->fresh();
    }
}
