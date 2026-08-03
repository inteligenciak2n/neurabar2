<?php

namespace Tests\Feature\Menu;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use RefreshAllDatabases;

    private function makeToken(Venue $venue): string
    {
        return rtrim(base64_encode(json_encode(['v' => $venue->id])), '=');
    }

    public function test_suspended_venue_cannot_serve_the_public_menu(): void
    {
        $venue = Venue::factory()->create(['active' => true]);

        CorporationSubscription::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Suspended,
        ]);

        $this->get(route('guest.menu', $this->makeToken($venue)))
            ->assertStatus(503);
    }

    public function test_public_menu_returns_active_products_without_authentication(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        Product::factory()->create(['category_id' => $category->id, 'active' => true, 'name' => 'Visible']);
        Product::factory()->inactive()->create(['category_id' => $category->id, 'name' => 'Hidden']);

        $this->get(route('guest.menu', $this->makeToken($venue)))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Menu')
                ->where('venue.name', $venue->name)
            );
    }

    public function test_inexistent_venue_returns_404(): void
    {
        $token = rtrim(base64_encode(json_encode(['v' => '00000000-0000-0000-0000-000000000000'])), '=');

        $this->get(route('guest.menu', $token))->assertNotFound();
    }

    public function test_inactive_products_do_not_appear_in_public_menu(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $menu = Menu::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        Product::factory()->create(['category_id' => $category->id, 'active' => true, 'name' => 'Active Product']);
        Product::factory()->inactive()->create(['category_id' => $category->id, 'name' => 'Inactive Product']);

        $this->get(route('guest.menu', $this->makeToken($venue)))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Menu')
                ->has('categories', 1, fn ($cat) => $cat
                    ->has('products', 1)
                    ->has('products.0', fn ($product) => $product
                        ->where('name', 'Active Product')
                        ->etc()
                    )
                    ->etc()
                )
            );
    }
}
