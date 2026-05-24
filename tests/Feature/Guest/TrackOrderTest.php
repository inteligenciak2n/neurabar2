<?php

namespace Tests\Feature\Guest;

use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Settings\PreparationStatus;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_order_tracking_page(): void
    {
        $venue = Venue::factory()->create();
        $attendance = Attendance::factory()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);

        $this->get(route('orders.track', $order->id))->assertOk();
    }

    public function test_tracking_only_shows_items_with_show_to_customer_status(): void
    {
        $venue = Venue::factory()->create();
        $attendance = Attendance::factory()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);

        $visibleStatus = PreparationStatus::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Ready',
            'show_to_customer' => true,
        ]);
        $hiddenStatus = PreparationStatus::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Pending',
            'show_to_customer' => false,
        ]);

        $visibleItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'preparation_status_id' => $visibleStatus->id,
        ]);
        $hiddenItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'preparation_status_id' => $hiddenStatus->id,
        ]);

        $response = $this->get(route('orders.track', $order->id));
        $response->assertOk();

        $items = $response->original->getData()['page']['props']['order']['items'];
        $itemIds = collect($items)->pluck('id')->toArray();

        $this->assertContains($visibleItem->id, $itemIds);
        $this->assertNotContains($hiddenItem->id, $itemIds);
    }

    public function test_tracking_returns_404_for_invalid_order(): void
    {
        $this->get(route('orders.track', 'nonexistent-uuid'))->assertNotFound();
    }

    public function test_tracking_does_not_expose_prices(): void
    {
        $venue = Venue::factory()->create();
        $attendance = Attendance::factory()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);

        $status = PreparationStatus::factory()->create(['venue_id' => $venue->id, 'show_to_customer' => true]);
        OrderItem::factory()->create(['order_id' => $order->id, 'preparation_status_id' => $status->id]);

        $response = $this->get(route('orders.track', $order->id));
        $items = $response->original->getData()['page']['props']['order']['items'];

        $this->assertArrayNotHasKey('unit_price', $items[0] ?? []);
    }

    public function test_service_request_order_is_not_trackable(): void
    {
        $venue = Venue::factory()->create();
        $attendance = Attendance::factory()->create([
            'venue_id' => $venue->id,
            'channel' => 'service_request',
        ]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);

        $this->get(route('orders.track', $order->id))->assertNotFound();
    }
}
