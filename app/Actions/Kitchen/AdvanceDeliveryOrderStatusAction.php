<?php

namespace App\Actions\Kitchen;

use App\Actions\Orders\CloseAttendanceAction;
use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Events\Kitchen\OrderStatusUpdated;
use App\Models\Orders\Order;
use App\Models\Payment\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdvanceDeliveryOrderStatusAction
{
    public function __construct(
        private readonly CloseAttendanceAction $closeAttendanceAction,
        private readonly PaymentService $paymentService,
    ) {}

    public function execute(Order $order): Order
    {
        $order->loadMissing('attendance.deliveryOrder.paymentMethods');

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

        DB::connection($order->getConnectionName())->transaction(function () use ($order, $deliveryOrder, $nextStatus): void {
            $order->update(['status' => $nextStatus]);

            if ($nextStatus === OrderStatus::Delivered) {
                $this->chargePaymentIfNeeded($order, $deliveryOrder);
                $this->closeAttendanceAction->execute($order->attendance);
            }
        });

        $order = $order->fresh('attendance');

        event(new OrderStatusUpdated($order));

        return $order;
    }

    /**
     * The guest checkout only records the chosen payment method(s) (DeliveryOrderPaymentMethod);
     * the charge is only recognized as revenue once the order is actually delivered/picked up.
     */
    private function chargePaymentIfNeeded(Order $order, mixed $deliveryOrder): void
    {
        if ($order->attendance->payment()->exists()) {
            return;
        }

        $totals = $this->paymentService->calculateTotal($order->attendance, 0, (float) $deliveryOrder->delivery_fee);

        $payment = Payment::withoutGlobalScopes()->create([
            'attendance_id' => $order->attendance->id,
            'items_total' => $totals['items_total'],
            'cover_charge_total' => $totals['cover_charge_total'],
            'service_fee_total' => $totals['service_fee_total'],
            'delivery_fee_total' => $totals['delivery_fee_total'],
            'grand_total' => $totals['grand_total'],
            'party_size' => 0,
        ]);

        foreach ($deliveryOrder->paymentMethods as $method) {
            $payment->paymentItems()->create([
                'method' => $method->method,
                'amount' => $method->amount,
                'notes' => $method->notes,
            ]);
        }
    }
}
