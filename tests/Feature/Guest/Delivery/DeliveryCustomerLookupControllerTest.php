<?php

namespace Tests\Feature\Guest\Delivery;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\GuestTokenService;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class DeliveryCustomerLookupControllerTest extends TestCase
{
    use RefreshAllDatabases;

    private function makeToken(Venue $venue): string
    {
        return app(GuestTokenService::class)->encodeVenueOnly($venue);
    }

    private function activateDelivery(Venue $venue): void
    {
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::Delivery->value,
            'status' => ModuleStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Delivery->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    public function test_returns_404_when_delivery_module_is_inactive(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->getJson("/delivery/{$token}/customer?phone=11999998888")->assertStatus(404);
    }

    public function test_response_never_leaks_the_customer_name_or_address(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        Customer::withoutGlobalScopes()->create([
            'corporation_id' => $venue->corporation_id,
            'name' => 'Maria Silva',
            'phone' => '11999998888',
        ]);

        $response = $this->getJson("/delivery/{$token}/customer?phone=11999998888");

        $response->assertOk();
        $response->assertExactJson(['found' => true]);
    }

    public function test_returns_not_found_for_an_unknown_phone(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        $response = $this->getJson("/delivery/{$token}/customer?phone=11999998888");

        $response->assertOk();
        $response->assertExactJson(['found' => false]);
    }

    public function test_customer_from_another_corporation_is_not_found(): void
    {
        $venueA = Venue::factory()->create(['active' => true]);
        $venueB = Venue::factory()->create(['active' => true, 'corporation_id' => Corporation::factory()->create()->id]);
        $this->activateDelivery($venueA);
        $token = $this->makeToken($venueA);

        Customer::withoutGlobalScopes()->create([
            'corporation_id' => $venueB->corporation_id,
            'name' => 'Maria Silva',
            'phone' => '11999998888',
        ]);

        $response = $this->getJson("/delivery/{$token}/customer?phone=11999998888");

        $response->assertOk();
        $response->assertExactJson(['found' => false]);
    }
}
