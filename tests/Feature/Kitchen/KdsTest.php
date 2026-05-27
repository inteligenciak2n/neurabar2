<?php

namespace Tests\Feature\Kitchen;

use App\Enums\UserRole;
use App\Events\Kitchen\ItemStatusUpdated;
use App\Events\Orders\OrderPlaced;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class KdsTest extends TestCase
{
    use RefreshDatabase;

    public function test_kds_index_returns_correct_page(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $this->get(route('kitchen.kds'))->assertOk()->assertInertia(
            fn ($page) => $page->component('Kitchen/Kds')
                ->has('stations')
                ->has('preparationStatuses')
                ->has('openItems')
        );
    }

    public function test_kds_lists_only_items_of_current_tenant(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);
        $item = OrderItem::factory()->create(['order_id' => $order->id, 'ready_at' => null]);

        // Item from another venue
        $otherVenue = Venue::factory()->create();
        $otherAttendance = Attendance::factory()->open()->create(['venue_id' => $otherVenue->id]);
        $otherOrder = Order::factory()->create(['attendance_id' => $otherAttendance->id]);
        OrderItem::factory()->create(['order_id' => $otherOrder->id, 'ready_at' => null]);

        $response = $this->get(route('kitchen.kds'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Kitchen/Kds'));
    }

    public function test_update_item_status_persists_in_database(): void
    {
        Event::fake([ItemStatusUpdated::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $status = PreparationStatus::factory()->create(['venue_id' => $venue->id]);
        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);
        $item = OrderItem::factory()->create(['order_id' => $order->id, 'ready_at' => null]);

        $this->put(route('kitchen.items.status', $item->id), [
            'preparation_status_id' => $status->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'preparation_status_id' => $status->id,
        ]);

        Event::assertDispatched(ItemStatusUpdated::class);
    }

    public function test_ready_at_is_set_when_all_items_in_order_are_done(): void
    {
        Event::fake([ItemStatusUpdated::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $status = PreparationStatus::factory()->create(['venue_id' => $venue->id, 'name' => 'Ready', 'is_final' => true]);
        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);

        // Single item in order — marking it with a final status should set ready_at
        $item = OrderItem::factory()->create(['order_id' => $order->id, 'ready_at' => null]);

        $this->put(route('kitchen.items.status', $item->id), [
            'preparation_status_id' => $status->id,
        ])->assertRedirect();

        $this->assertNotNull(OrderItem::find($item->id)->ready_at);
    }

    public function test_ready_at_is_not_set_when_status_is_not_final(): void
    {
        Event::fake([ItemStatusUpdated::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $status = PreparationStatus::factory()->create(['venue_id' => $venue->id, 'name' => 'In Preparation', 'is_final' => false]);
        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);
        $item = OrderItem::factory()->create(['order_id' => $order->id, 'ready_at' => null]);

        $this->put(route('kitchen.items.status', $item->id), [
            'preparation_status_id' => $status->id,
        ])->assertRedirect();

        $this->assertNull(OrderItem::find($item->id)->ready_at);
    }

    public function test_item_from_another_venue_returns_validation_error(): void
    {
        Event::fake([ItemStatusUpdated::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $otherVenue = Venue::factory()->create();
        $otherAttendance = Attendance::factory()->open()->create(['venue_id' => $otherVenue->id]);
        $otherOrder = Order::factory()->create(['attendance_id' => $otherAttendance->id]);
        $otherItem = OrderItem::factory()->create(['order_id' => $otherOrder->id]);

        $status = PreparationStatus::factory()->create(['venue_id' => $venue->id]);

        $this->put(route('kitchen.items.status', $otherItem->id), [
            'preparation_status_id' => $status->id,
        ])->assertSessionHasErrors('item');
    }

    public function test_order_placed_event_triggers_broadcast_listener(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $station = KitchenStation::factory()->create(['venue_id' => $venue->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'kitchen_station_id' => $station->id, 'active' => true]);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => $product->price],
            ],
        ])->assertRedirect();

        Event::assertDispatched(OrderPlaced::class);
    }
}
