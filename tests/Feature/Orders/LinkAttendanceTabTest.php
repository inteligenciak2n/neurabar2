<?php

namespace Tests\Feature\Orders;

use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\Tab;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class LinkAttendanceTabTest extends TestCase
{
    use RefreshAllDatabases;

    private function addItem(Attendance $attendance, float $price = 50.00): OrderItem
    {
        $menu = Menu::factory()->create(['venue_id' => $attendance->venue_id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => $price]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);

        return OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $price,
        ]);
    }

    public function test_waiter_can_link_a_free_tab_to_an_unassigned_attendance(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $location = ServiceLocation::factory()->create(['venue_id' => $venue->id]);
        $tab = Tab::factory()->create(['venue_id' => $venue->id, 'code' => '7']);
        $attendance = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $location->id,
        ]);
        $this->addItem($attendance);

        $this->from(route('attendances.index'))
            ->put(route('attendances.link-tab', $attendance), [
                'tab_id' => $tab->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'tab_id' => $tab->id,
        ]);
    }

    public function test_linking_a_tab_already_open_at_the_same_table_merges_the_orders(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $location = ServiceLocation::factory()->create(['venue_id' => $venue->id]);
        $tab = Tab::factory()->create(['venue_id' => $venue->id, 'code' => '3']);
        $tabbed = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $location->id,
            'tab_id' => $tab->id,
        ]);
        $this->addItem($tabbed, 10);
        $unlinked = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $location->id,
        ]);
        $movedItem = $this->addItem($unlinked, 20);

        $this->from(route('attendances.index'))
            ->put(route('attendances.link-tab', $unlinked), [
                'tab_id' => $tab->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $movedItem->order_id,
            'attendance_id' => $tabbed->id,
        ]);
        $this->assertDatabaseMissing('attendances', ['id' => $unlinked->id]);
    }

    public function test_cannot_link_a_tab_that_is_open_at_another_table(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $tableA = ServiceLocation::factory()->create(['venue_id' => $venue->id, 'name' => 'Mesa 1']);
        $tableB = ServiceLocation::factory()->create(['venue_id' => $venue->id, 'name' => 'Mesa 2']);
        $tab = Tab::factory()->create(['venue_id' => $venue->id, 'code' => '8']);
        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $tableA->id,
            'tab_id' => $tab->id,
        ]);
        $unlinked = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $tableB->id,
        ]);
        $this->addItem($unlinked);

        $this->from(route('attendances.index'))
            ->put(route('attendances.link-tab', $unlinked), [
                'tab_id' => $tab->id,
            ])
            ->assertSessionHasErrors('tab_id');
    }

    public function test_cannot_close_a_table_while_an_item_has_no_tab(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'cover_charge' => 10.00,
            'service_fee_percent' => 10.00,
        ]);

        $location = ServiceLocation::factory()->create(['venue_id' => $venue->id]);
        $tab = Tab::factory()->create(['venue_id' => $venue->id, 'code' => '3']);
        $tabbed = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $location->id,
            'tab_id' => $tab->id,
            'party_size' => 2,
        ]);
        $this->addItem($tabbed, 100.00);

        $unlinked = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $location->id,
        ]);
        $this->addItem($unlinked, 20.00);

        $this->post(route('payment.store', $tabbed->id), [
            'party_size' => 2,
            'methods' => [
                ['type' => 'cash', 'amount' => 132.00],
            ],
        ])->assertSessionHasErrors('attendance');

        $this->assertDatabaseHas('attendances', [
            'id' => $tabbed->id,
            'status' => 'open',
        ]);
    }

    public function test_attendance_without_a_table_can_still_be_closed(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'cover_charge' => 10.00,
            'service_fee_percent' => 10.00,
        ]);

        $attendance = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'service_location_id' => null,
            'party_size' => 2,
        ]);
        $this->addItem($attendance, 100.00);

        $this->post(route('payment.store', $attendance->id), [
            'party_size' => 2,
            'methods' => [
                ['type' => 'cash', 'amount' => 132.00],
            ],
        ])->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'closed',
        ]);
    }
}
