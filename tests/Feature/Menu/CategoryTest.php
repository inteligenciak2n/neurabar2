<?php

namespace Tests\Feature\Menu;

use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_categories(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('menu.index'))->assertOk();
    }

    public function test_owner_can_create_category(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('menu.categories.store'), ['name' => 'Drinks'])
            ->assertRedirect();

        $menu = Menu::withoutGlobalScopes()->where('venue_id', $venue->id)->first();
        $this->assertNotNull($menu);
        $this->assertDatabaseHas('menu_categories', ['menu_id' => $menu->id, 'name' => 'Drinks']);
    }

    public function test_owner_can_update_category(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id, 'name' => 'Old']);

        $this->put(route('menu.categories.update', $category->id), ['name' => 'Updated'])
            ->assertRedirect();

        $this->assertDatabaseHas('menu_categories', ['id' => $category->id, 'name' => 'Updated']);
    }

    public function test_owner_can_delete_category(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->delete(route('menu.categories.destroy', $category->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('menu_categories', ['id' => $category->id]);
    }

    public function test_reorder_persists_correct_order(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $cat1 = Category::factory()->create(['menu_id' => $menu->id, 'sort_order' => 1]);
        $cat2 = Category::factory()->create(['menu_id' => $menu->id, 'sort_order' => 2]);
        $cat3 = Category::factory()->create(['menu_id' => $menu->id, 'sort_order' => 3]);

        $this->post(route('menu.categories.reorder'), ['ids' => [$cat3->id, $cat1->id, $cat2->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('menu_categories', ['id' => $cat3->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('menu_categories', ['id' => $cat1->id, 'sort_order' => 2]);
        $this->assertDatabaseHas('menu_categories', ['id' => $cat2->id, 'sort_order' => 3]);
    }

    public function test_attendant_cannot_access_menu_edit(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('menu.index'))->assertForbidden();
    }

    public function test_category_from_other_tenant_returns_404(): void
    {
        $this->loginAs(UserRole::Owner);

        $otherVenue = Venue::factory()->create();
        $otherMenu = Menu::factory()->create(['venue_id' => $otherVenue->id]);
        $otherCategory = Category::factory()->create(['menu_id' => $otherMenu->id]);

        // TenantScope prevents loading this category, causing model not found
        $this->put(route('menu.categories.update', $otherCategory->id), ['name' => 'Hack'])
            ->assertStatus(404);
    }
}
