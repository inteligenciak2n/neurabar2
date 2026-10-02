<?php

namespace Tests\Feature\Menu;

use App\Enums\ServiceLocationType;
use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Settings\ServiceLocation;
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
            'type' => ServiceLocationType::Table,
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
        ]);
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $category = Category::factory()->create(['menu_id' => $menu->id, 'name' => 'Drinks']);
        Product::factory()->create([
            'category_id' => $category->id,
            'active' => true,
            'name' => 'Deliverable',
            'available_for_delivery' => true,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'active' => true,
            'name' => 'Table only',
            'available_for_delivery' => false,
        ]);

        $this->get(route('menu.preview.customer.delivery'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Delivery/Menu')
                ->where('preview', true)
                ->where('token', null)
                ->where('venue.name', $venue->name)
                ->where('venue.description', 'Petiscos, chopp e música ao vivo.')
                ->missing('serviceLocation')
                ->has('categories', 1, fn ($cat) => $cat
                    ->where('name', 'Drinks')
                    ->has('products', 1)
                    ->has('products.0', fn ($product) => $product
                        ->where('name', 'Deliverable')
                        ->etc()
                    )
                    ->etc()
                )
            );
    }

    public function test_owner_can_preview_the_attendant_menu(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id, 'active' => true]);
        $category = Category::factory()->create(['menu_id' => $menu->id, 'name' => 'Mains']);
        Product::factory()->create(['category_id' => $category->id, 'active' => true, 'name' => 'Steak']);
        Combo::factory()->create(['venue_id' => $venue->id, 'active' => true, 'name' => 'Lunch Combo']);
        Combo::factory()->create(['venue_id' => $venue->id, 'active' => false, 'name' => 'Hidden Combo']);

        $this->get(route('menu.preview.attendant'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Menu/PreviewAttendant')
                ->where('venue.name', $venue->name)
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
