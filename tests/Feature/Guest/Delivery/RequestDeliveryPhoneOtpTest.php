<?php

namespace Tests\Feature\Guest\Delivery;

use App\Contracts\Sms\SmsProviderContract;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\GuestTokenService;
use App\Services\Sms\FakeSmsProvider;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RequestDeliveryPhoneOtpTest extends TestCase
{
    use RefreshAllDatabases;

    protected function setUp(): void
    {
        parent::setUp();

        // Ignore whatever SMS_PROVIDER is set to locally (e.g. real Twilio credentials).
        $this->app->bind(SmsProviderContract::class, FakeSmsProvider::class);
    }

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

    public function test_returns_reference_id_and_sets_a_guest_session_cookie(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        Customer::withoutGlobalScopes()->create([
            'corporation_id' => $venue->corporation_id,
            'name' => 'Maria Silva',
            'phone' => '11999998888',
        ]);

        $response = $this->postJson("/delivery/{$token}/phone/otp", ['phone' => '11999998888']);

        $response->assertOk();
        $this->assertNotEmpty($response->json('reference_id'));
        $this->assertDatabaseCount('guest_sessions', 1);
    }

    public function test_returns_404_for_a_phone_with_no_matching_customer(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        $this->postJson("/delivery/{$token}/phone/otp", ['phone' => '11999998888'])->assertStatus(404);
    }

    public function test_returns_404_when_delivery_module_is_inactive(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->postJson("/delivery/{$token}/phone/otp", ['phone' => '11999998888'])->assertStatus(404);
    }

    public function test_rate_limit_blocks_after_too_many_requests_for_the_same_phone(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        Customer::withoutGlobalScopes()->create([
            'corporation_id' => $venue->corporation_id,
            'name' => 'Maria Silva',
            'phone' => '11999998888',
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson("/delivery/{$token}/phone/otp", ['phone' => '11999998888'])->assertOk();
        }

        $this->postJson("/delivery/{$token}/phone/otp", ['phone' => '11999998888'])->assertStatus(429);
    }
}
