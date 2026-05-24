<?php

namespace Tests\Feature\Menu;

use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_menu_returns_active_products_without_authentication(): void
    {
        $venue = Venue::factory()->withSlug('test-bar-123')->create();
        $menu = Menu::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        Product::factory()->create(['category_id' => $category->id, 'active' => true, 'name' => 'Visible']);
        Product::factory()->inactive()->create(['category_id' => $category->id, 'name' => 'Hidden']);

        $response = $this->get(route('menu.public', 'test-bar-123'))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Guest/Menu')
            ->where('venue.name', $venue->name)
        );
    }

    public function test_inexistent_slug_returns_404(): void
    {
        $this->get(route('menu.public', 'nonexistent-slug'))->assertNotFound();
    }

    public function test_inactive_products_do_not_appear_in_public_menu(): void
    {
        $venue = Venue::factory()->withSlug('test-bar-456')->create();
        $menu = Menu::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        Product::factory()->inactive()->create(['category_id' => $category->id, 'name' => 'Inactive Product']);

        $response = $this->get(route('menu.public', 'test-bar-456'))->assertOk();

        $response->assertInertia(fn ($page) => $page->component('Guest/Menu'));
        // Inactive product should not appear in any category products
        $categories = $response->viewData('page')['props']['categories'] ?? [];
        foreach ($categories as $cat) {
            foreach ($cat['products'] ?? [] as $product) {
                $this->assertNotEquals('Inactive Product', $product['name']);
            }
        }
    }
}
