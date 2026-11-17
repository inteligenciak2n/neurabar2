<?php

namespace Tests\Feature\Menu;

use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\Tab;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class MenuPreviewTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_preview_the_customer_menu(): void
    {
        $venue = Venue::factory()->create([
            'description' => 'Petiscos, chopp e música ao vivo.',
        ]);
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $category = Category::factory()->create(['menu_id' => $menu->id, 'name' => 'Drinks']);
        Product::factory()->create(['category_id' => $category->id, 'active' => true, 'name' => 'Visible']);
        Product::factory()->inactive()->create(['category_id' => $category->id, 'name' => 'Hidden']);
        ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesa 7',
            'active' => true,
        ]);

        $this->get(route('menu.preview.customer'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Menu')
                ->where('preview', true)
                ->where('token', null)
                ->where('venue.name', $venue->name)
                ->where('venue.description', 'Petiscos, chopp e música ao vivo.')
                ->where('serviceLocation.name', 'Mesa 7')
                ->where('hasDirectWaiter', true)
                ->where('hasMenu', true)
                ->where('waiterCallPhrases', ['Pedir a conta', 'Gelo', 'Guardanapo', 'Talheres'])
                ->has('categories', 1, fn ($cat) => $cat
                    ->where('name', 'Drinks')
                    ->has('products', 1)
                    ->has('products.0', fn ($product) => $product
                        ->where('name', 'Visible')
                        ->etc()
                    )
                    ->etc()
                )
            );
    }

    public function test_owner_can_preview_the_customer_delivery_menu(): void
    {
        $venue = Venue::factory()->create([
            'description' => 'Petiscos, chopp e música ao vivo.',
            'street' => 'Rua das Flores',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'Londrina',
            'state' => 'PR',
        ]);
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $category = Category::factory()->create(['menu_id' => $menu->id, 'name' => 'Drinks']);
        Product::factory()->create([
            'category_id' => $category->id,
            'active' => true,
            'name' => 'Deliverable',
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'active' => true,
            'name' => 'Table only',
        ]);
        Category::factory()->create(['menu_id' => $menu->id, 'name' => 'Empty']);

        $this->get(route('menu.preview.customer.delivery'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Delivery/Menu')
                ->where('preview', true)
                ->where('token', null)
                ->where('venue.name', $venue->name)
                ->where('venue.description', 'Petiscos, chopp e música ao vivo.')
                ->where('venue.street', 'Rua das Flores')
                ->where('venue.number', '100')
                ->where('venue.city', 'Londrina')
                ->missing('serviceLocation')
                ->has('categories', 1, fn ($cat) => $cat
                    ->where('name', 'Drinks')
                    ->has('products', 2)
                    ->has('products.0', fn ($product) => $product
                        ->where('name', 'Deliverable')
                        ->etc()
                    )
                    ->has('products.1', fn ($product) => $product
                        ->where('name', 'Table only')
                        ->etc()
                    )
                    ->etc()
                )
            );
    }

    public function test_owner_can_preview_the_attendant_menu(): void
    {
        $venue = Venue::factory()->create([
            'description' => 'Petiscos, chopp e música ao vivo.',
        ]);
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $category = Category::factory()->create(['menu_id' => $menu->id, 'name' => 'Mains']);
        Product::factory()->create(['category_id' => $category->id, 'active' => true, 'name' => 'Steak']);
        Combo::factory()->create(['venue_id' => $venue->id, 'active' => true, 'name' => 'Lunch Combo']);
        Combo::factory()->create(['venue_id' => $venue->id, 'active' => false, 'name' => 'Hidden Combo']);
        $mesa = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesa',
            'color' => '#2563eb',
            'active' => true,
            'sort_order' => 1,
            'uses_service_location' => true,
            'requires_tab' => true,
            'requires_customer_identifier' => false,
        ]);
        AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Delivery',
            'color' => '#16a34a',
            'active' => true,
            'sort_order' => 2,
            'uses_service_location' => false,
            'requires_tab' => false,
            'requires_customer_identifier' => true,
        ]);
        ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesa 1',
            'default_attendance_channel_id' => $mesa->id,
            'active' => true,
        ]);
        Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => 'A1',
            'active' => true,
        ]);
        AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Hidden Channel',
            'active' => false,
            'sort_order' => 3,
        ]);

        $this->get(route('menu.preview.attendant'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Menu/PreviewAttendant')
                ->where('preview', true)
                ->where('venue.name', $venue->name)
                ->where('venue.description', 'Petiscos, chopp e música ao vivo.')
                ->has('channels', 2)
                ->where('channels.0.name', 'Mesa')
                ->where('channels.0.color', '#2563eb')
                ->where('channels.0.uses_service_location', true)
                ->where('channels.0.requires_tab', true)
                ->where('channels.1.name', 'Delivery')
                ->where('channels.1.uses_service_location', false)
                ->where('channels.1.requires_customer_identifier', true)
                ->has('serviceLocations', 1)
                ->has('tabs', 1)
                ->has('attendances')
                ->has('categories', 1, fn ($cat) => $cat
                    ->where('name', 'Mains')
                    ->has('products', 1)
                    ->has('products.0', fn ($product) => $product
                        ->where('name', 'Steak')
                        ->etc()
                    )
                    ->etc()
                )
                ->has('combos', 1, fn ($combo) => $combo
                    ->where('name', 'Lunch Combo')
                    ->etc()
                )
            );
    }

    public function test_attendant_preview_includes_item_delivery_status(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'active' => true,
            'requires_tab' => true,
            'uses_service_location' => true,
        ]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesa 5',
            'default_attendance_channel_id' => $channel->id,
            'active' => true,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '7',
            'active' => true,
        ]);
        $pending = PreparationStatus::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Pendente',
            'is_final' => false,
        ]);
        $ready = PreparationStatus::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Pronto',
            'is_final' => true,
        ]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'active' => true,
            'name' => 'Cerveja Lata',
        ]);
        $attendance = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'service_location_id' => $location->id,
            'tab_id' => $tab->id,
        ]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'preparation_status_id' => $pending->id,
            'ready_at' => null,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'preparation_status_id' => $ready->id,
            'ready_at' => now(),
        ]);

        $this->get(route('menu.preview.attendant'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Menu/PreviewAttendant')
                ->has('attendances', 1)
                ->where('attendances.0.tab.code', '7')
                ->where('attendances.0.service_location.name', 'Mesa 5')
                ->has('attendances.0.orders.0.items', 2)
                ->where('attendances.0.orders.0.items.0.preparation_status.is_final', false)
                ->where('attendances.0.orders.0.items.1.preparation_status.is_final', true)
            );
    }

    public function test_unauthenticated_users_cannot_preview_the_menu(): void
    {
        $this->get(route('menu.preview.customer'))->assertRedirect();
        $this->get(route('menu.preview.customer.delivery'))->assertRedirect();
        $this->get(route('menu.preview.attendant'))->assertRedirect();
    }

    public function test_attendant_cannot_preview_the_menu(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('menu.preview.customer'))->assertForbidden();
        $this->get(route('menu.preview.customer.delivery'))->assertForbidden();
        $this->get(route('menu.preview.attendant'))->assertForbidden();
    }
}
