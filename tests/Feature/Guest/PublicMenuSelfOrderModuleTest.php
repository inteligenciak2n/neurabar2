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

    public function test_public_menu_returns_not_found_when_self_order_is_inactive(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->get("/g/{$token}/menu")->assertStatus(404);
    }

    public function test_public_menu_is_available_when_self_order_is_active(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateSelfOrder($venue);
        $token = $this->makeToken($venue);

        $this->get("/g/{$token}/menu")->assertOk();
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
