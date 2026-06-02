<?php

namespace Tests\Feature\Menu;

use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ComboTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_create_combo(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->post(route('menu.combos.store'), [
            'name' => 'Meal Deal',
            'description' => 'Best combo',
            'price' => 35.00,
            'active' => true,
            'items' => [
                ['product_id' => $product->id, 'variation_id' => null, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('combos', ['name' => 'Meal Deal', 'venue_id' => $venue->id]);
    }

    public function test_combo_creation_generates_correct_combo_items(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $productA = Product::factory()->create(['category_id' => $category->id]);
        $productB = Product::factory()->create(['category_id' => $category->id]);

        $this->post(route('menu.combos.store'), [
            'name' => 'Duo',
            'price' => 50.00,
            'active' => true,
            'items' => [
                ['product_id' => $productA->id, 'variation_id' => null, 'quantity' => 1],
                ['product_id' => $productB->id, 'variation_id' => null, 'quantity' => 2],
            ],
        ]);

        $combo = Combo::where('venue_id', $venue->id)->first();
        $this->assertNotNull($combo);
        $this->assertCount(2, $combo->items);
        $this->assertEquals(2, $combo->items->where('product_id', $productB->id)->first()->quantity);
    }

    public function test_owner_can_delete_combo(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $combo = Combo::factory()->create(['venue_id' => $venue->id]);

        $this->delete(route('menu.combos.destroy', $combo->id))->assertRedirect();

        $this->assertDatabaseMissing('combos', ['id' => $combo->id]);
    }

    public function test_combo_from_other_venue_cannot_be_deleted(): void
    {
        $venue = Venue::factory()->create();
        $otherVenue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $combo = Combo::factory()->create(['venue_id' => $otherVenue->id]);

        $this->delete(route('menu.combos.destroy', $combo->id))->assertNotFound();
    }

    public function test_ordering_combo_generates_one_order_item_per_combo_item(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $productA = Product::factory()->create(['category_id' => $category->id, 'active' => true, 'price' => 10.00]);
        $productB = Product::factory()->create(['category_id' => $category->id, 'active' => true, 'price' => 5.00]);

        $combo = Combo::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $combo->items()->createMany([
            ['product_id' => $productA->id, 'variation_id' => null, 'quantity' => 1],
            ['product_id' => $productB->id, 'variation_id' => null, 'quantity' => 1],
        ]);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [],
            'combos' => [
                ['combo_id' => $combo->id],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
        $order = $attendance->orders()->first();
        $this->assertCount(2, $order->items);

        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $productA->id, 'combo_id' => $combo->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $productB->id, 'combo_id' => $combo->id]);
    }
}
