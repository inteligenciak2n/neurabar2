<?php

namespace Tests\Feature\Guest;

use App\Events\Orders\GuestSignaled;
use App\Models\GuestSession;
use App\Models\Tenant\Venue;
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

    public function test_signal_dispatches_event(): void
    {
        Event::fake([GuestSignaled::class]);

        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/session", ['pin' => '1234'])->assertOk();
        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();
        $encryptedCookie = $session->guest_token;

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $encryptedCookie)
            ->postJson("/g/{$token}/signal", ['message' => 'Falta de água', 'signal_only' => false])
            ->assertOk();

        Event::assertDispatched(GuestSignaled::class);
    }

    public function test_signal_without_session_returns_forbidden(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/signal", ['signal_only' => true])
            ->assertStatus(403);
    }
}
