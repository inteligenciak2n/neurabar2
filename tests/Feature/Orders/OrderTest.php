<?php

namespace Tests\Feature\Orders;

use App\Enums\UserRole;
use App\Events\Orders\OrderPlaced;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\ModifierGroup;
use App\Models\Menu\ModifierOption;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_place_order(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 20.00, 'active' => true]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 20.00,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['attendance_id' => $attendance->id, 'order_number' => 1]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 20.00]);

        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_order_number_increments_correctly(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'active' => true]);

        $payload = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => $product->price],
            ],
        ];

        $this->post(route('attendances.orders.store', $attendance->id), $payload);
        $this->post(route('attendances.orders.store', $attendance->id), $payload);

        $this->assertDatabaseHas('orders', ['attendance_id' => $attendance->id, 'order_number' => 1]);
        $this->assertDatabaseHas('orders', ['attendance_id' => $attendance->id, 'order_number' => 2]);
    }

    public function test_unit_price_is_stored_as_snapshot(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 30.00, 'active' => true]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30.00],
            ],
        ]);

        // Change product price after order
        $product->update(['price' => 99.99]);

        // Stored unit_price should still be 30.00
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'unit_price' => 30.00]);
    }

    public function test_order_on_closed_attendance_fails(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'active' => true]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => $product->price],
            ],
        ])->assertSessionHasErrors('attendance');
    }

    public function test_cross_tenant_product_is_rejected(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);

        $otherVenue = Venue::factory()->create();
        $otherMenu = Menu::factory()->create(['venue_id' => $otherVenue->id]);
        $otherCategory = Category::factory()->create(['menu_id' => $otherMenu->id]);
        $otherProduct = Product::factory()->create(['category_id' => $otherCategory->id, 'active' => true]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [
                ['product_id' => $otherProduct->id, 'quantity' => 1, 'unit_price' => $otherProduct->price],
            ],
        ])->assertSessionHasErrors('items.0.product_id');
    }

    public function test_empty_cart_returns_validation_error(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [],
        ])->assertSessionHasErrors('items');
    }

    public function test_tampered_unit_price_returns_validation_error(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 50.00, 'active' => true]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 0.01],
            ],
        ])->assertSessionHasErrors('items.0.unit_price');
    }

    public function test_modifier_extra_price_snapshot_is_saved_on_order(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 20.00, 'active' => true]);

        $modifierGroup = ModifierGroup::factory()->create(['venue_id' => $venue->id]);
        $modifierOption = ModifierOption::factory()->create([
            'modifier_group_id' => $modifierGroup->id,
            'extra_price' => 5.00,
            'active' => true,
        ]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 20.00,
                    'modifiers' => [
                        ['modifier_option_id' => $modifierOption->id],
                    ],
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('order_item_modifiers', [
            'modifier_option_id' => $modifierOption->id,
            'extra_price_snapshot' => 5.00,
        ]);
    }

    public function test_cross_tenant_modifier_option_is_rejected(): void
    {
        Event::fake([OrderPlaced::class]);

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 20.00, 'active' => true]);

        $otherVenue = Venue::factory()->create();
        $otherGroup = ModifierGroup::factory()->create(['venue_id' => $otherVenue->id]);
        $otherOption = ModifierOption::factory()->create([
            'modifier_group_id' => $otherGroup->id,
            'extra_price' => 5.00,
            'active' => true,
        ]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 20.00,
                    'modifiers' => [
                        ['modifier_option_id' => $otherOption->id],
                    ],
                ],
            ],
        ])->assertSessionHasErrors('items.0.modifiers.0.modifier_option_id');
    }
}
