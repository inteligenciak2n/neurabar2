<?php

namespace Tests\Feature\Guest;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Events\Orders\ServiceRequestCreated;
use App\Events\Orders\ServiceRequestUpdated;
use App\Models\GuestSession;
use App\Models\Orders\ServiceRequest;
use App\Models\Settings\ServiceLocation;
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
            ->assertOk()
            ->assertJsonPath('message', 'Falta de água')
            ->assertJsonStructure(['ok', 'id', 'message', 'created_at']);

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

    public function test_guest_can_cancel_an_open_waiter_call(): void
    {
        Event::fake([ServiceRequestUpdated::class]);

        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDirectWaiter($venue);
        $location = ServiceLocation::factory()->create(['venue_id' => $venue->id, 'name' => 'Mesa 4']);
        $token = rtrim(base64_encode(json_encode([
            'v' => $venue->id,
            'l' => $location->id,
        ])), '=');

        $this->postJson("/g/{$token}/session", [])->assertOk();
        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();

        $create = $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$token}/signal", ['message' => 'Gelo']);

        $create->assertOk();
        $id = $create->json('id');

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$token}/signal/{$id}/cancel")
            ->assertOk();

        $this->assertDatabaseHas('service_requests', [
            'id' => $id,
            'status' => ServiceRequestStatus::Resolved->value,
            'message' => 'Gelo',
        ]);

        Event::assertDispatched(ServiceRequestUpdated::class);
    }

    public function test_table_menu_exposes_the_pending_waiter_call(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDirectWaiter($venue);
        $location = ServiceLocation::factory()->create(['venue_id' => $venue->id, 'name' => 'Mesa 4']);
        $token = rtrim(base64_encode(json_encode([
            'v' => $venue->id,
            'l' => $location->id,
        ])), '=');

        ServiceRequest::factory()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $location->id,
            'type' => ServiceRequestType::Message,
            'message' => 'Guardanapo',
            'status' => ServiceRequestStatus::Pending,
        ]);

        $this->get("/g/{$token}/menu")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Menu')
                ->where('hasDirectWaiter', true)
                ->where('pendingWaiterCall.message', 'Guardanapo')
            );
    }
}
