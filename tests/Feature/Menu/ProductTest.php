<?php

namespace Tests\Feature\Menu;

use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Settings\KitchenStation;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_create_product(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Burger', 'category_id' => $category->id]);
    }

    public function test_product_creation_with_kitchen_station_from_same_tenant(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $station = KitchenStation::factory()->create(['venue_id' => $venue->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'kitchen_station_id' => $station->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Burger', 'kitchen_station_id' => $station->id]);
    }

    public function test_kitchen_station_from_other_tenant_is_rejected(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $otherVenue = Venue::factory()->create();
        $otherStation = KitchenStation::factory()->create(['venue_id' => $otherVenue->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'kitchen_station_id' => $otherStation->id,
        ])->assertSessionHasErrors('kitchen_station_id');
    }

    public function test_toggle_active_alternates_correctly(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'active' => true]);

        $this->post(route('menu.products.toggle', $product->id))->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'active' => false]);

        $this->post(route('menu.products.toggle', $product->id))->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'active' => true]);
    }
}
