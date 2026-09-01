<?php

namespace App\Services\Payment;

use App\Models\Orders\Attendance;
use App\Models\Settings\VenueSettings;

class PaymentService
{
    /**
     * Calculate payment totals for an attendance.
     *
     * @return array{items_total: float, cover_charge_total: float, service_fee_total: float, delivery_fee_total: float, grand_total: float}
     */
    public function calculateTotal(Attendance $attendance, ?int $partySize = null, float $deliveryFee = 0.0): array
    {
        $attendance->loadMissing('orders.items.modifiers');

        $itemsTotal = 0.0;

        foreach ($attendance->orders as $order) {
            foreach ($order->items as $item) {
                $modifiersTotal = $item->modifiers->sum(fn ($m) => (float) $m->extra_price_snapshot);
                $itemsTotal += ((float) $item->unit_price + $modifiersTotal) * $item->quantity;
            }
        }

        $settings = VenueSettings::withoutGlobalScopes()
            ->where('venue_id', $attendance->venue_id)
            ->firstOrFail();

        $partySize = max($partySize ?? (int) $attendance->party_size, 0);
        $coverChargeTotal = $partySize > 0 ? (float) $settings->cover_charge * $partySize : 0.0;

        $subtotal = $itemsTotal + $coverChargeTotal;
        $serviceFeePct = (float) $settings->service_fee_percent;
        $serviceFeeTotal = round($subtotal * ($serviceFeePct / 100), 2);
        $deliveryFeeTotal = round($deliveryFee, 2);
        $grandTotal = round($subtotal + $serviceFeeTotal + $deliveryFeeTotal, 2);

        return [
            'items_total' => round($itemsTotal, 2),
            'cover_charge_total' => round($coverChargeTotal, 2),
            'service_fee_total' => $serviceFeeTotal,
            'delivery_fee_total' => $deliveryFeeTotal,
            'grand_total' => $grandTotal,
        ];
    }

    public function splitTotal(float $total, int $partySize): float
    {
        if ($partySize <= 0) {
            return $total;
        }

        return round($total / $partySize, 2);
    }
}
