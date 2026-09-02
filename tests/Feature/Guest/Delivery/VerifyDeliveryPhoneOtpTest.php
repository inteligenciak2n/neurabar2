<?php

namespace Tests\Feature\Guest\Delivery;

use App\Contracts\Sms\SmsProviderContract;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\GuestSession;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\GuestTokenService;
use App\Services\Sms\FakeSmsProvider;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class VerifyDeliveryPhoneOtpTest extends TestCase
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

    /**
     * Requests an OTP (which creates the GuestSession) and returns its guest_token cookie value.
     */
    private function requestOtpAndGetCookie(string $token, Venue $venue, string $phone): string
    {
        $this->postJson("/delivery/{$token}/phone/otp", ['phone' => $phone])->assertOk();

        return GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first()->guest_token;
    }

    public function test_marks_the_session_phone_as_verified_on_a_valid_code(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        Customer::withoutGlobalScopes()->create([
            'corporation_id' => $venue->corporation_id,
            'name' => 'Maria Silva',
            'phone' => '11999998888',
        ]);

        $cookie = $this->requestOtpAndGetCookie($token, $venue, '11999998888');

        $response = $this->withCredentials()->withUnencryptedCookie('guest_token', $cookie)
            ->postJson("/delivery/{$token}/phone/otp/verify", [
                'phone' => '11999998888',
                'reference_id' => 'fake-reference',
                'code' => '1234',
            ]);

        $response->assertOk()->assertExactJson(['verified' => true]);

        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();
        $this->assertSame('11999998888', $session->verified_phone);
        $this->assertNotNull($session->phone_verified_at);
    }

    public function test_returns_verified_false_when_the_provider_rejects_the_code(): void
    {
        $this->mock(SmsProviderContract::class, function ($mock) {
            $mock->shouldReceive('requestOtp')->andReturn(['reference_id' => 'fake-reference']);
            $mock->shouldReceive('validateOtp')->andReturn(false);
        });

        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        Customer::withoutGlobalScopes()->create([
            'corporation_id' => $venue->corporation_id,
            'name' => 'Maria Silva',
            'phone' => '11999998888',
        ]);

        $cookie = $this->requestOtpAndGetCookie($token, $venue, '11999998888');

        $response = $this->withCredentials()->withUnencryptedCookie('guest_token', $cookie)
            ->postJson("/delivery/{$token}/phone/otp/verify", [
                'phone' => '11999998888',
                'reference_id' => 'fake-reference',
                'code' => '0000',
            ]);

        $response->assertOk()->assertExactJson(['verified' => false]);

        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();
        $this->assertNull($session->phone_verified_at);
    }

    public function test_returns_forbidden_without_an_active_session(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        $token = $this->makeToken($venue);

        $this->postJson("/delivery/{$token}/phone/otp/verify", [
            'phone' => '11999998888',
            'reference_id' => 'fake-reference',
            'code' => '1234',
        ])->assertForbidden();
    }
}
