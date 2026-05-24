<?php

namespace Tests\Feature\Menu;

use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\ModifierGroup;
use App\Models\Menu\Product;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_modifier_group(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('menu.modifier-groups.store'), [
            'name' => 'Extras',
            'required' => false,
            'multiple_selection' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('modifier_groups', ['name' => 'Extras', 'venue_id' => $venue->id]);
    }

    public function test_owner_can_add_option_to_group(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $group = ModifierGroup::factory()->create(['venue_id' => $venue->id]);

        $this->post(route('menu.modifier-groups.options.store', $group->id), [
            'name' => 'Extra Cheese',
            'extra_price' => 2.50,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('modifier_options', ['modifier_group_id' => $group->id, 'name' => 'Extra Cheese']);
    }

    public function test_modifier_group_from_other_tenant_not_visible(): void
    {
        $venue = Venue::factory()->create();
        $otherVenue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $otherGroup = ModifierGroup::factory()->create(['venue_id' => $otherVenue->id]);

        $response = $this->get(route('menu.modifier-groups.index'));
        $response->assertOk();

        $props = $response->original->getData()['page']['props'] ?? [];
        $groupIds = collect($props['modifierGroups'] ?? [])->pluck('id')->toArray();
        $this->assertNotContains($otherGroup->id, $groupIds);
    }

    public function test_owner_can_sync_modifier_groups_to_product(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id]);
        $group = ModifierGroup::factory()->create(['venue_id' => $venue->id]);

        $this->put(route('menu.products.modifier-groups.sync', $product->id), [
            'modifier_group_ids' => [$group->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('product_modifier_group', [
            'product_id' => $product->id,
            'modifier_group_id' => $group->id,
        ]);
    }
}
