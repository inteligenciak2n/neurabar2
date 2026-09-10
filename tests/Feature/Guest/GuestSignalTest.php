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

class GuestSignalTest extends TestCase
{
    use RefreshAllDatabases;

    private function makeToken(Venue $venue): string
    {
        return rtrim(base64_encode(json_encode(['v' => $venue->id])), '=');
    }

    private function activateDirectWaiter(Venue $venue): void
    {
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
    }

    public function test_signal_creates_a_message_service_request(): void
    {
        Event::fake([ServiceRequestCreated::class]);

        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDirectWaiter($venue);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/session", ['pin' => '1234'])->assertOk();
        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$token}/signal", ['message' => 'Falta de água', 'signal_only' => false])
            ->assertOk();

        Event::assertDispatched(ServiceRequestCreated::class, fn ($event) => $event->serviceRequest->type === ServiceRequestType::Message
            && $event->serviceRequest->message === 'Falta de água'
            && $event->serviceRequest->venue_id === $venue->id);

        $this->assertDatabaseHas('service_requests', [
            'venue_id' => $venue->id,
            'type' => ServiceRequestType::Message->value,
            'message' => 'Falta de água',
        ]);
    }

    public function test_signal_without_session_returns_forbidden(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDirectWaiter($venue);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/signal", ['signal_only' => true])
            ->assertStatus(403);
    }

    public function test_signal_returns_not_found_when_direct_waiter_is_inactive(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/session", ['pin' => '1234'])->assertOk();
        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$token}/signal", ['message' => 'Falta de água'])
            ->assertStatus(404);
    }
}
