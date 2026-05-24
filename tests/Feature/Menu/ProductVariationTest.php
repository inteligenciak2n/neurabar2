<?php

namespace Tests\Feature\Menu;

use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_variation(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->post(route('menu.products.variations.store', $product->id), [
            'name' => 'Large',
            'price' => 25.90,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('product_variations', [
            'product_id' => $product->id,
            'name' => 'Large',
            'price' => 25.90,
        ]);
    }

    public function test_owner_can_update_variation(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id]);
        $variation = ProductVariation::factory()->create(['product_id' => $product->id, 'price' => 10.00]);

        $this->put(route('menu.products.variations.update', [$product->id, $variation->id]), [
            'name' => 'Updated',
            'price' => 15.00,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('product_variations', ['id' => $variation->id, 'price' => 15.00]);
    }

    public function test_order_uses_variation_price_as_snapshot(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 10.00]);
        $variation = ProductVariation::factory()->create(['product_id' => $product->id, 'price' => 18.50]);
        $attendance = Attendance::factory()->create(['venue_id' => $venue->id]);

        $this->post(route('attendances.orders.store', $attendance->id), [
            'items' => [[
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'quantity' => 1,
            ]],
        ])->assertRedirect();

        $order = Order::where('attendance_id', $attendance->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(18.50, (float) $order->items->first()->unit_price);
    }

    public function test_owner_from_other_venue_cannot_create_variation(): void
    {
        $venue = Venue::factory()->create();
        $otherVenue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $otherVenue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->post(route('menu.products.variations.store', $product->id), [
            'name' => 'Forbidden',
            'price' => 10.00,
            'active' => true,
        ])->assertForbidden();
    }
}
