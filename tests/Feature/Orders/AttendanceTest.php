<?php

namespace Tests\Feature\Orders;

use App\Enums\UserRole;
use App\Events\Orders\OrderPlaced;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Orders\VenueCustomer;
use App\Models\Orders\VenueCustomerAddress;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\DeliveryFeeZone;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\Tab;
use App\Models\Tenant\Venue;
use Illuminate\Support\Facades\Event;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_attendant_can_open_attendance(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);

        $response = $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($attendance);
        $response->assertRedirect(route('orders.take', $attendance));

        $this->assertDatabaseHas('attendances', [
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => null,
            'status' => 'open',
        ]);
    }

    public function test_opening_attendance_without_identifier_fails_when_channel_requires_it(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_customer_identifier' => true,
        ]);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
        ])->assertSessionHasErrors('customer_identifier');
    }

    public function test_opening_attendance_with_identifier_when_channel_requires_it(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_customer_identifier' => true,
        ]);

        $response = $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'customer_identifier' => 'Table 7',
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($attendance);
        $response->assertRedirect(route('orders.take', $attendance));
    }

    public function test_index_returns_only_tenant_attendances(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        Attendance::factory()->open()->create(['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id]);

        $otherVenue = Venue::factory()->create();
        $otherChannel = AttendanceChannel::factory()->create(['venue_id' => $otherVenue->id]);
        Attendance::factory()->open()->create(['venue_id' => $otherVenue->id, 'attendance_channel_id' => $otherChannel->id]);

        $response = $this->get(route('attendances.index'))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Attendances/Index')
            ->has('attendances', 1)
            ->where('startWithForm', false)
        );
    }

    public function test_open_attendances_page_lists_active_channels(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $mesas = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
            'active' => true,
            'sort_order' => 1,
        ]);
        AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Balcão',
            'active' => true,
            'sort_order' => 2,
        ]);
        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $mesas->id,
        ]);
        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $mesas->id,
        ]);

        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Attendances/Index')
                ->where('startWithForm', false)
                ->has('channels', 2)
                ->where('channels.0.name', 'Mesas')
                ->where('channels.1.name', 'Balcão')
                ->has('attendances', 2)
            );
    }

    public function test_create_page_opens_the_new_attendance_form(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('attendances.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Menu/PreviewAttendant')
                ->where('preview', false)
                ->where('startMode', 'order')
                ->has('channels')
                ->has('categories')
            );
    }

    public function test_create_page_accepts_start_mode_query(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('attendances.create', ['mode' => 'table']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Menu/PreviewAttendant')
                ->where('preview', false)
                ->where('startMode', 'table')
            );
    }

    public function test_create_page_lists_channels_by_sort_order(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $second = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Delivery',
            'sort_order' => 2,
        ]);
        $first = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
            'sort_order' => 1,
        ]);

        $this->get(route('attendances.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Menu/PreviewAttendant')
                ->where('preview', false)
                ->where('channels.0.id', $first->id)
                ->where('channels.1.id', $second->id)
            );
    }

    public function test_create_page_includes_channel_color(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'color' => '#2563eb',
        ]);

        $this->get(route('attendances.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Menu/PreviewAttendant')
                ->where('preview', false)
                ->where('channels.0.id', $channel->id)
                ->where('channels.0.color', '#2563eb')
            );
    }

    public function test_take_order_page_preloads_tab_and_table(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
            'requires_tab' => true,
            'uses_service_location' => true,
            'active' => true,
        ]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 30',
            'default_attendance_channel_id' => $channel->id,
            'active' => true,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '3',
            'active' => true,
        ]);
        $attendance = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
            'service_location_id' => $location->id,
            'customer_identifier' => 'Mesas 30',
        ]);

        $this->get(route('orders.take', $attendance))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Menu/PreviewAttendant')
                ->where('preview', false)
                ->where('attendance.id', $attendance->id)
                ->where('attendance.attendance_channel_id', $channel->id)
                ->where('attendance.tab_id', $tab->id)
                ->where('attendance.tab.code', '3')
                ->where('attendance.service_location_id', $location->id)
                ->where('attendance.service_location.name', 'Mesas 30')
            );
    }

    public function test_take_order_page_includes_identifier_when_table_was_stored_as_identifier(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
            'requires_tab' => true,
            'uses_service_location' => true,
            'active' => true,
        ]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 30',
            'default_attendance_channel_id' => $channel->id,
            'active' => true,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '3',
            'active' => true,
        ]);
        $attendance = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
            'service_location_id' => null,
            'customer_identifier' => 'Mesas 30',
        ]);

        $this->get(route('orders.take', $attendance))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Menu/PreviewAttendant')
                ->where('preview', false)
                ->where('attendance.tab_id', $tab->id)
                ->where('attendance.service_location_id', null)
                ->where('attendance.customer_identifier', 'Mesas 30')
                ->has('serviceLocations', 1)
                ->where('serviceLocations.0.id', $location->id)
                ->where('serviceLocations.0.name', 'Mesas 30')
            );
    }

    public function test_other_tenant_attendance_is_not_accessible(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $otherVenue = Venue::factory()->create();
        $otherChannel = AttendanceChannel::factory()->create(['venue_id' => $otherVenue->id]);
        $otherAttendance = Attendance::factory()->open()->create(['venue_id' => $otherVenue->id, 'attendance_channel_id' => $otherChannel->id]);

        $this->get(route('attendances.show', $otherAttendance->id))->assertNotFound();
    }

    public function test_attendant_can_open_attendance_with_a_tab_card_code(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);

        $response = $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'code' => ' c-042 ',
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($attendance);
        $response->assertRedirect(route('orders.take', $attendance));
        $this->assertDatabaseHas('attendances', ['venue_id' => $venue->id, 'code' => 'C-042', 'status' => 'open']);
    }

    public function test_tab_card_code_cannot_be_used_by_two_open_attendances(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        Attendance::factory()->open()->create(['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id, 'code' => '42']);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'code' => '42',
        ])->assertSessionHasErrors('code');

        $this->assertSame(1, Attendance::withoutGlobalScopes()->where('code', '42')->count());
    }

    public function test_tab_card_code_is_available_again_after_the_attendance_closes(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        Attendance::factory()->closed()->create(['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id, 'code' => '42']);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'code' => '42',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, Attendance::withoutGlobalScopes()->open()->where('code', '42')->count());
    }

    public function test_tab_card_code_is_scoped_per_venue(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        $otherVenue = Venue::factory()->create();
        Attendance::factory()->open()->create(['venue_id' => $otherVenue->id, 'code' => '42']);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'code' => '42',
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_updating_an_attendance_can_keep_its_own_code_but_not_take_another(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        $mine = Attendance::factory()->open()->create(['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id, 'code' => '10']);
        Attendance::factory()->open()->create(['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id, 'code' => '20']);

        $this->put(route('attendances.update', $mine->id), ['code' => '10', 'notes' => 'ok'])
            ->assertSessionDoesntHaveErrors();

        $this->put(route('attendances.update', $mine->id), ['code' => '20'])
            ->assertSessionHasErrors('code');
    }

    public function test_opening_attendance_without_tab_fails_when_channel_requires_it(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
        ]);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
        ])->assertSessionHasErrors('tab_id');
    }

    public function test_opening_attendance_with_tab_when_channel_requires_it(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '5',
        ]);

        $response = $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($attendance);
        $response->assertRedirect(route('orders.take', $attendance));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'tab_id' => $tab->id,
        ]);
    }

    public function test_cannot_open_attendance_with_a_tab_already_in_use(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '10',
        ]);

        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
        ]);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
        ])->assertSessionHasErrors('tab_id');
    }

    public function test_index_omits_tabs_that_are_already_open(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
        ]);
        $freeTab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '5',
        ]);
        $busyTab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '10',
        ]);

        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $busyTab->id,
        ]);

        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Attendances/Index')
                ->has('tabs', 1)
                ->where('tabs.0.id', $freeTab->id)
            );
    }

    public function test_index_includes_products_for_the_order_locator(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        $attendance = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
        ]);
        $product = Product::factory()->create(['name' => 'Suco de laranja']);
        $order = Order::factory()->create([
            'attendance_id' => $attendance->id,
            'order_number' => 12,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Attendances/Index')
                ->where('attendances.0.orders.0.order_number', 12)
                ->where('attendances.0.orders.0.items.0.product.name', 'Suco de laranja')
            );
    }

    public function test_shared_tab_can_be_opened_on_any_channel_that_requires_it(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Salão',
            'requires_tab' => true,
        ]);
        $otherChannel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Área externa',
            'requires_tab' => true,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => 'A15',
        ]);

        $response = $this->post(route('attendances.store'), [
            'attendance_channel_id' => $otherChannel->id,
            'tab_id' => $tab->id,
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($attendance);
        $response->assertRedirect(route('orders.take', $attendance));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'attendance_channel_id' => $otherChannel->id,
            'tab_id' => $tab->id,
        ]);
    }

    public function test_attendant_take_order_opens_attendance_and_places_the_order(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => false,
            'requires_customer_identifier' => false,
            'active' => true,
        ]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 12.50,
            'active' => true,
        ]);

        $this->post(route('attendances.take-order'), [
            'attendance_channel_id' => $channel->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 12.50,
                ],
            ],
        ])->assertRedirect();

        $attendance = Attendance::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('attendance_channel_id', $channel->id)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertDatabaseHas('orders', ['attendance_id' => $attendance->id, 'order_number' => 1]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 1]);
        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_take_order_reuses_an_open_attendance_on_the_same_tab(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
            'active' => true,
        ]);
        $tab = Tab::factory()->create(['venue_id' => $venue->id, 'code' => '7']);
        $existing = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
        ]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 8.00,
            'active' => true,
        ]);

        $this->post(route('attendances.take-order'), [
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 8.00,
                ],
            ],
        ])->assertRedirect();

        $this->assertEquals(1, Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->count());
        $this->assertDatabaseHas('orders', ['attendance_id' => $existing->id]);
    }

    public function test_take_order_requires_name_and_phone_when_channel_asks_for_customer(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => false,
            'requires_customer_identifier' => true,
            'active' => true,
        ]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 12.50,
            'active' => true,
        ]);
        $items = [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 12.50,
            ],
        ];

        $this->post(route('attendances.take-order'), [
            'attendance_channel_id' => $channel->id,
            'items' => $items,
        ])->assertSessionHasErrors('customer_name');

        $this->post(route('attendances.take-order'), [
            'attendance_channel_id' => $channel->id,
            'customer_name' => 'João',
            'items' => $items,
        ])->assertSessionHasErrors('customer_phone');

        $this->assertDatabaseCount('venue_customers', 0);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_take_order_stores_a_venue_customer_exclusive_to_the_venue(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $otherVenue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        VenueCustomer::withoutGlobalScopes()->create([
            'venue_id' => $otherVenue->id,
            'name' => 'Outro estabelecimento',
            'phone' => '43988887777',
        ]);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => false,
            'requires_customer_identifier' => true,
            'active' => true,
        ]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 12.50,
            'active' => true,
        ]);

        $this->post(route('attendances.take-order'), [
            'attendance_channel_id' => $channel->id,
            'customer_name' => 'Maria Silva',
            'customer_phone' => '(43) 98888-7777',
            'customer_address' => 'Rua das Flores, 100, Centro',
            'customer_comment' => 'Sem cebola',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 12.50,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('venue_customers', [
            'venue_id' => $venue->id,
            'name' => 'Maria Silva',
            'phone' => '43988887777',
        ]);
        $this->assertDatabaseHas('venue_customers', [
            'venue_id' => $otherVenue->id,
            'name' => 'Outro estabelecimento',
            'phone' => '43988887777',
        ]);

        $attendance = Attendance::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->venue_customer_id);
        $this->assertSame('Maria Silva', $attendance->customer_identifier);
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'venue_customer_id' => $attendance->venue_customer_id,
            'customer_identifier' => 'Maria Silva',
        ]);
    }

    public function test_take_order_reuses_the_venue_customer_by_phone(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $existingCustomer = VenueCustomer::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'João',
            'phone' => '43911112222',
            'address' => 'Rua A, 1',
        ]);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => false,
            'requires_customer_identifier' => true,
            'active' => true,
        ]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 8.00,
            'active' => true,
        ]);

        $this->post(route('attendances.take-order'), [
            'attendance_channel_id' => $channel->id,
            'customer_name' => 'João Pedro',
            'customer_phone' => '43911112222',
            'customer_address' => 'Rua B, 2',
            'customer_comment' => 'Portão azul',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 8.00,
                ],
            ],
        ])->assertRedirect();

        $this->assertEquals(1, VenueCustomer::withoutGlobalScopes()->where('venue_id', $venue->id)->count());
        $this->assertDatabaseHas('venue_customers', [
            'id' => $existingCustomer->id,
            'name' => 'João Pedro',
            'phone' => '43911112222',
        ]);
    }

    public function test_take_order_requires_delivery_address_on_delivery_channels(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => false,
            'requires_customer_identifier' => true,
            'uses_service_location' => false,
            'active' => true,
        ]);
        DeliveryFeeZone::factory()->create([
            'venue_id' => $venue->id,
            'label' => 'Centro',
            'fee' => 7.5,
            'active' => true,
        ]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 12.50,
            'active' => true,
        ]);

        $this->post(route('attendances.take-order'), [
            'attendance_channel_id' => $channel->id,
            'customer_name' => 'Maria',
            'customer_phone' => '43988887777',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 12.50,
                ],
            ],
        ])->assertSessionHasErrors('delivery_address.zip_code');
    }

    public function test_take_order_stores_a_delivery_address_snapshot_for_the_venue_customer(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => false,
            'requires_customer_identifier' => true,
            'uses_service_location' => false,
            'active' => true,
        ]);
        $zone = DeliveryFeeZone::factory()->create([
            'venue_id' => $venue->id,
            'label' => 'Centro',
            'fee' => 8.00,
            'active' => true,
        ]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 12.50,
            'active' => true,
        ]);

        $this->post(route('attendances.take-order'), [
            'attendance_channel_id' => $channel->id,
            'customer_name' => 'Maria Silva',
            'customer_phone' => '43988887777',
            'delivery_address' => [
                'zip_code' => '86010-000',
                'street' => 'Rua X',
                'number' => '120',
                'complement' => 'Apto 32',
                'delivery_fee_zone_id' => $zone->id,
                'neighborhood' => 'Centro',
                'city' => 'Londrina',
                'state' => 'PR',
                'reference_point' => 'portão azul',
                'delivery_phone' => '(43) 99999-9999',
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 12.50,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('venue_customer_addresses', [
            'venue_id' => $venue->id,
            'zip_code' => '86010000',
            'street' => 'Rua X',
            'number' => '120',
            'complement' => 'Apto 32',
            'neighborhood' => 'Centro',
            'city' => 'Londrina',
            'state' => 'PR',
            'reference_point' => 'portão azul',
            'delivery_phone' => '43999999999',
            'delivery_fee_zone_id' => $zone->id,
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();
        $this->assertNotNull($attendance);

        $this->assertDatabaseHas('attendance_delivery_addresses', [
            'attendance_id' => $attendance->id,
            'street' => 'Rua X',
            'number' => '120',
            'neighborhood' => 'Centro',
            'delivery_fee' => 8.00,
            'reference_point' => 'portão azul',
        ]);

        $this->get(route('attendances.customers.lookup', ['phone' => '43988887777']))
            ->assertOk()
            ->assertJsonPath('customer.name', 'Maria Silva')
            ->assertJsonPath('customer.addresses.0.street', 'Rua X')
            ->assertJsonPath('customer.addresses.0.number', '120');
    }

    public function test_customer_lookup_returns_only_the_current_venue_addresses(): void
    {
        $venue = Venue::factory()->create();
        $otherVenue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $mine = VenueCustomer::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Ana',
            'phone' => '43977776666',
        ]);
        VenueCustomerAddress::factory()->create([
            'venue_id' => $venue->id,
            'venue_customer_id' => $mine->id,
            'street' => 'Rua da Ana',
            'number' => '10',
            'zip_code' => '86020000',
            'neighborhood' => 'Centro',
            'city' => 'Londrina',
            'state' => 'PR',
        ]);

        $other = VenueCustomer::withoutGlobalScopes()->create([
            'venue_id' => $otherVenue->id,
            'name' => 'Outra',
            'phone' => '43977776666',
        ]);
        VenueCustomerAddress::withoutGlobalScopes()->create([
            'venue_id' => $otherVenue->id,
            'venue_customer_id' => $other->id,
            'zip_code' => '01001000',
            'street' => 'Rua de Fora',
            'number' => '1',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        $this->get(route('attendances.customers.lookup', ['phone' => '43977776666']))
            ->assertOk()
            ->assertJsonPath('customer.name', 'Ana')
            ->assertJsonCount(1, 'customer.addresses')
            ->assertJsonPath('customer.addresses.0.street', 'Rua da Ana');
    }

    public function test_attendant_can_register_a_neighborhood_from_the_order_form(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $this->postJson(route('attendances.neighborhoods.store'), [
            'name' => 'Gleba Palhano',
            'zip_code' => '86050-230',
            'fee' => 12.5,
        ])->assertOk()
            ->assertJsonPath('neighborhood.name', 'Gleba Palhano')
            ->assertJsonPath('neighborhood.fee', 12.5)
            ->assertJsonPath('neighborhood.has_label', true);

        $this->assertDatabaseHas('delivery_fee_zones', [
            'venue_id' => $venue->id,
            'label' => 'Gleba Palhano',
            'zip_code_start' => 86050230,
            'zip_code_end' => 86050230,
            'fee' => 12.50,
            'active' => true,
        ]);
    }

    public function test_registering_a_neighborhood_reuses_the_existing_labeled_zone(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $zone = DeliveryFeeZone::factory()->create([
            'venue_id' => $venue->id,
            'label' => 'Jardim São Paulo',
            'fee' => 9,
            'active' => true,
        ]);

        $this->postJson(route('attendances.neighborhoods.store'), [
            'name' => 'Jd. Sao Paulo',
            'zip_code' => '86010000',
            'fee' => 20,
        ])->assertOk()
            ->assertJsonPath('neighborhood.id', $zone->id)
            ->assertJsonPath('neighborhood.fee', 9);

        $this->assertEquals(1, DeliveryFeeZone::withoutGlobalScopes()->where('venue_id', $venue->id)->count());
    }

    public function test_take_order_accepts_a_neighborhood_name_without_a_registered_zone(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => false,
            'requires_customer_identifier' => true,
            'uses_service_location' => false,
            'active' => true,
        ]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 12.50,
            'active' => true,
        ]);

        $this->post(route('attendances.take-order'), [
            'attendance_channel_id' => $channel->id,
            'customer_name' => 'Maria',
            'customer_phone' => '43988887777',
            'delivery_address' => [
                'zip_code' => '86020030',
                'street' => 'Rua Paranaguá',
                'number' => '399',
                'neighborhood' => 'Centro',
                'city' => 'Londrina',
                'state' => 'PR',
                'delivery_fee' => 15,
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 12.50,
                ],
            ],
        ])->assertRedirect();

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();
        $this->assertNotNull($attendance);
        $this->assertDatabaseHas('attendance_delivery_addresses', [
            'attendance_id' => $attendance->id,
            'neighborhood' => 'Centro',
            'delivery_fee' => 15.00,
            'delivery_fee_zone_id' => null,
        ]);
    }

    public function test_attendant_can_register_a_consumption_location_from_the_order_form(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
            'uses_service_location' => true,
            'active' => true,
        ]);

        $this->postJson(route('attendances.locations.store'), [
            'attendance_channel_id' => $channel->id,
            'number' => 13,
        ])->assertOk()
            ->assertJsonPath('location.name', 'Mesas 13')
            ->assertJsonPath('location.default_attendance_channel_id', $channel->id)
            ->assertJsonPath('location.active', true);

        $this->assertDatabaseHas('service_locations', [
            'venue_id' => $venue->id,
            'name' => 'Mesas 13',
            'default_attendance_channel_id' => $channel->id,
            'active' => true,
        ]);
    }

    public function test_registering_a_consumption_location_reuses_the_same_channel_name(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
            'uses_service_location' => true,
            'active' => true,
        ]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 13',
            'default_attendance_channel_id' => $channel->id,
            'active' => true,
        ]);

        $this->postJson(route('attendances.locations.store'), [
            'attendance_channel_id' => $channel->id,
            'number' => 13,
        ])->assertOk()
            ->assertJsonPath('location.id', $location->id);

        $this->assertEquals(1, ServiceLocation::withoutGlobalScopes()->where('venue_id', $venue->id)->count());
    }

    public function test_opening_attendance_with_stay_returns_to_the_board(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
            'uses_service_location' => true,
        ]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 30',
            'default_attendance_channel_id' => $channel->id,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '7',
        ]);

        $this->from(route('attendances.index'))
            ->post(route('attendances.store'), [
                'attendance_channel_id' => $channel->id,
                'tab_id' => $tab->id,
                'service_location_id' => $location->id,
                'stay' => true,
            ])
            ->assertRedirect(route('attendances.index'));

        $attendance = Attendance::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('tab_id', $tab->id)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame($location->id, $attendance->service_location_id);
        $this->assertSame(0, $attendance->orders()->count());
    }

    public function test_show_lists_all_tabs_and_categorized_items_for_the_table(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
            'uses_service_location' => true,
        ]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 30',
            'default_attendance_channel_id' => $channel->id,
        ]);
        $tabFive = Tab::factory()->create(['venue_id' => $venue->id, 'code' => '5']);
        $tabThree = Tab::factory()->create(['venue_id' => $venue->id, 'code' => '3']);
        $freeTab = Tab::factory()->create(['venue_id' => $venue->id, 'code' => '8']);

        $attendanceFive = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tabFive->id,
            'service_location_id' => $location->id,
        ]);
        $attendanceThree = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tabThree->id,
            'service_location_id' => $location->id,
        ]);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $drinks = Category::factory()->create([
            'menu_id' => $menu->id,
            'name' => 'Bebidas',
            'sort_order' => 1,
        ]);
        $product = Product::factory()->create([
            'category_id' => $drinks->id,
            'name' => 'Cerveja Lata',
            'price' => 6.90,
        ]);
        $pendingStatus = PreparationStatus::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Pendente',
            'is_final' => false,
        ]);
        $readyStatus = PreparationStatus::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Pronto',
            'is_final' => true,
        ]);

        $orderFive = Order::factory()->create(['attendance_id' => $attendanceFive->id, 'order_number' => 1]);
        OrderItem::factory()->create([
            'order_id' => $orderFive->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 6.90,
            'preparation_status_id' => $pendingStatus->id,
        ]);

        $orderThree = Order::factory()->create(['attendance_id' => $attendanceThree->id, 'order_number' => 2]);
        OrderItem::factory()->create([
            'order_id' => $orderThree->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 9.90,
            'preparation_status_id' => $readyStatus->id,
            'ready_at' => now(),
        ]);

        $this->get(route('attendances.show', $attendanceFive->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Attendances/Show')
                ->where('attendance.service_location.name', 'Mesas 30')
                ->has('tableAttendances', 2)
                ->has('availableTabs', 1)
                ->where('availableTabs.0.id', $freeTab->id)
                ->where('tableAttendances.0.tab.code', '3')
                ->where('tableAttendances.1.tab.code', '5')
                ->has('tableAttendances.0.orders')
                ->where('attendance.orders.0.items.0.product.category.name', 'Bebidas')
                ->where('attendance.orders.0.items.0.preparation_status.is_final', false)
            );
    }

    public function test_attendant_cannot_register_a_location_for_a_channel_that_does_not_use_one(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Delivery',
            'uses_service_location' => false,
            'active' => true,
        ]);

        $this->postJson(route('attendances.locations.store'), [
            'attendance_channel_id' => $channel->id,
            'number' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('attendance_channel_id');

        $this->assertDatabaseMissing('service_locations', [
            'venue_id' => $venue->id,
            'name' => 'Delivery 1',
        ]);
    }
}
