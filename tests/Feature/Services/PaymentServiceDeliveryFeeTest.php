<?php

namespace Tests\Feature\Services;

use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;
use App\Services\Payment\PaymentService;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PaymentServiceDeliveryFeeTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_calculate_total_includes_delivery_fee_in_grand_total(): void
    {
        $venue = Venue::factory()->create();
        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'cover_charge' => 0,
            'service_fee_percent' => 10,
        ]);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id, 'party_size' => 0]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'unit_price' => 100]);

        $totals = (new PaymentService)->calculateTotal($attendance->fresh(), 0, 15.0);

        $this->assertEquals(100, $totals['items_total']);
        $this->assertEquals(10, $totals['service_fee_total']);
        $this->assertEquals(15, $totals['delivery_fee_total']);
        $this->assertEquals(125, $totals['grand_total']);
    }

    public function test_calculate_total_defaults_delivery_fee_to_zero(): void
    {
        $venue = Venue::factory()->create();
        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'cover_charge' => 0,
            'service_fee_percent' => 0,
        ]);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id, 'party_size' => 0]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'unit_price' => 50]);

        $totals = (new PaymentService)->calculateTotal($attendance->fresh());

        $this->assertEquals(0, $totals['delivery_fee_total']);
        $this->assertEquals(50, $totals['grand_total']);
    }
}
