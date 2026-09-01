<?php

namespace Tests\Feature\Guest;

use App\Enums\ServiceRequestType;
use App\Events\Orders\ServiceRequestCreated;
use App\Models\GuestSession;
use App\Models\Tenant\Venue;
use Illuminate\Support\Facades\Event;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use RefreshAllDatabases;

    private function makeToken(Venue $venue): string
    {
        return rtrim(base64_encode(json_encode(['v' => $venue->id])), '=');
    }

    public function test_checkout_creates_a_checkout_service_request(): void
    {
        Event::fake([ServiceRequestCreated::class]);

        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/session", ['pin' => '1234'])->assertOk();
        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$token}/checkout")
            ->assertOk();

        $this->assertDatabaseHas('service_requests', [
            'venue_id' => $venue->id,
            'type' => ServiceRequestType::Checkout->value,
        ]);

        Event::assertDispatched(ServiceRequestCreated::class, fn ($event) => $event->serviceRequest->type === ServiceRequestType::Checkout);
    }

    public function test_checkout_without_session_returns_forbidden(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/checkout")->assertStatus(403);
    }
}
