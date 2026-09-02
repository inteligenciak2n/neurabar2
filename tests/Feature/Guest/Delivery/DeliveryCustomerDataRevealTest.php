<?php

namespace Tests\Feature\Guest\Delivery;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\GuestSession;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerAddress;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\GuestTokenService;
use Illuminate\Support\Str;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class DeliveryCustomerDataRevealTest extends TestCase
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

    private function verifiedSessionCookie(Venue $venue, string $phone): string
    {
        $session = GuestSession::withoutGlobalScopes()->create([
            'venue_id' => $venue->id,
            'guest_token' => (string) Str::uuid(),
            'verified_phone' => $phone,
            'phone_verified_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        return $session->guest_token;
    }

    public function test_returns_forbidden_when_phone_was_never_verified(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        Customer::withoutGlobalScopes()->create([
            'corporation_id' => $venue->corporation_id,
            'name' => 'Maria Silva',
            'phone' => '11999998888',
        ]);

        $this->getJson("/delivery/{$token}/customer/data?phone=11999998888")->assertForbidden();
    }

    public function test_returns_forbidden_when_verified_phone_does_not_match(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        Customer::withoutGlobalScopes()->create([
            'corporation_id' => $venue->corporation_id,
            'name' => 'Maria Silva',
            'phone' => '11999998888',
        ]);

        $cookie = $this->verifiedSessionCookie($venue, '11977776666');

        $this->withCredentials()->withUnencryptedCookie('guest_token', $cookie)
            ->getJson("/delivery/{$token}/customer/data?phone=11999998888")
            ->assertForbidden();
    }

    public function test_returns_name_and_default_address_when_phone_is_verified(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        $customer = Customer::withoutGlobalScopes()->create([
            'corporation_id' => $venue->corporation_id,
            'name' => 'Maria Silva',
            'phone' => '11999998888',
        ]);

        CustomerAddress::withoutGlobalScopes()->create([
            'customer_id' => $customer->id,
            'street' => 'Rua Antiga',
            'number' => '10',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01001000',
            'is_default' => false,
        ]);

        $defaultAddress = CustomerAddress::withoutGlobalScopes()->create([
            'customer_id' => $customer->id,
            'street' => 'Rua Nova',
            'number' => '20',
            'neighborhood' => 'Jardins',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01415000',
            'is_default' => true,
        ]);

        $cookie = $this->verifiedSessionCookie($venue, '11999998888');

        $response = $this->withCredentials()->withUnencryptedCookie('guest_token', $cookie)
            ->getJson("/delivery/{$token}/customer/data?phone=11999998888");

        $response->assertOk();
        $this->assertSame('Maria Silva', $response->json('name'));
        $this->assertSame($defaultAddress->id, $response->json('address.id'));
    }
}
