<?php

namespace Tests\Feature\Guest;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\ServiceRequestType;
use App\Events\Orders\ServiceRequestCreated;
use App\Models\GuestSession;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Illuminate\Support\Facades\Event;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RequestOrderAssistanceTest extends TestCase
{
    use RefreshAllDatabases;

    private function makeToken(Venue $venue): string
    {
        return rtrim(base64_encode(json_encode(['v' => $venue->id])), '=');
    }

    private function activateTaker(Venue $venue): void
    {
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::Taker->value,
            'status' => ModuleStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Taker->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    public function test_returns_not_found_when_taker_module_is_inactive(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/session", ['pin' => '1234'])->assertOk();
        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$token}/request-order")
            ->assertStatus(404);
    }

    public function test_creates_a_call_to_order_service_request_when_taker_is_active(): void
    {
        Event::fake([ServiceRequestCreated::class]);

        $venue = Venue::factory()->create(['active' => true]);
        $this->activateTaker($venue);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/session", ['pin' => '1234'])->assertOk();
        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$token}/request-order")
            ->assertOk();

        $this->assertDatabaseHas('service_requests', [
            'venue_id' => $venue->id,
            'type' => ServiceRequestType::CallToOrder->value,
        ]);

        Event::assertDispatched(ServiceRequestCreated::class, fn ($event) => $event->serviceRequest->type === ServiceRequestType::CallToOrder);
    }

    public function test_does_not_record_direct_waiter_usage_for_call_to_order(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateTaker($venue);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/session", ['pin' => '1234'])->assertOk();
        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$token}/request-order")
            ->assertOk();

        $this->assertDatabaseCount('venue_usage_records', 0);
    }
}
