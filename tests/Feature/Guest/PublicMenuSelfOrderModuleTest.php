<?php

namespace Tests\Feature\Guest;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PublicMenuSelfOrderModuleTest extends TestCase
{
    use RefreshAllDatabases;

    private function makeToken(Venue $venue): string
    {
        return rtrim(base64_encode(json_encode(['v' => $venue->id])), '=');
    }

    private function activateSelfOrder(Venue $venue): void
    {
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::SelfOrder->value,
            'status' => ModuleStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::SelfOrder->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    public function test_public_menu_hides_the_catalog_when_the_menu_module_is_inactive(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->get("/g/{$token}/menu")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Menu')
                ->where('hasMenu', false)
                ->where('hasDirectWaiter', false)
                ->has('categories', 0)
            );
    }

    public function test_public_menu_catalog_is_available_when_the_menu_module_is_active(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::Menu->value,
            'status' => ModuleStatus::Active,
        ]);
        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Menu->value,
            'status' => ModuleStatus::Active,
        ]);
        $token = $this->makeToken($venue);

        $this->get("/g/{$token}/menu")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Menu')
                ->where('hasMenu', true)
            );
    }

    public function test_public_menu_shows_call_waiter_when_direct_waiter_is_active(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::DirectWaiter->value,
            'status' => ModuleStatus::Active,
        ]);
        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::DirectWaiter->value,
            'status' => ModuleStatus::Active,
        ]);
        $token = $this->makeToken($venue);

        $this->get("/g/{$token}/menu")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Menu')
                ->where('hasDirectWaiter', true)
            );
    }

    public function test_guest_orders_index_returns_not_found_when_self_order_is_inactive(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->getJson("/g/{$token}/orders?pin=1234")->assertStatus(404);
    }

    public function test_guest_orders_index_requires_a_session_when_self_order_is_active(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateSelfOrder($venue);
        $token = $this->makeToken($venue);

        // No guest session created — module check passes, session check fails with 403
        // instead of 404, proving the module gate is not what blocked the request.
        $this->getJson("/g/{$token}/orders?pin=1234")->assertStatus(403);
    }
}
