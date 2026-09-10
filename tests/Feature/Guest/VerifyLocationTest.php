<?php

namespace Tests\Feature\Guest;

use App\Models\GuestSession;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class VerifyLocationTest extends TestCase
{
    use RefreshAllDatabases;

    private function makeToken(Venue $venue): string
    {
        return rtrim(base64_encode(json_encode(['v' => $venue->id])), '=');
    }

    private function createGuestSession(Venue $venue, string $token): GuestSession
    {
        $this->postJson("/g/{$token}/session", ['pin' => '1234'])->assertOk();

        return GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();
    }

    public function test_allows_when_guest_is_within_range_of_the_venue(): void
    {
        $venue = Venue::factory()->create(['active' => true, 'latitude' => -23.5505, 'longitude' => -46.6333]);
        $token = $this->makeToken($venue);
        $session = $this->createGuestSession($venue, $token);

        $response = $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$token}/verify-location", ['lat' => -23.5505, 'lng' => -46.6333])
            ->assertOk();

        $response->assertJson(['allowed' => true]);
        $this->assertDatabaseHas('guest_sessions', ['id' => $session->id, 'geolocation_verified' => true]);
    }

    public function test_blocks_when_guest_is_far_from_the_venue(): void
    {
        $venue = Venue::factory()->create(['active' => true, 'latitude' => -23.5505, 'longitude' => -46.6333]);
        $token = $this->makeToken($venue);
        $session = $this->createGuestSession($venue, $token);

        $response = $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            // São Paulo x Rio de Janeiro — muito além do raio permitido.
            ->postJson("/g/{$token}/verify-location", ['lat' => -22.9068, 'lng' => -43.1729])
            ->assertOk();

        $response->assertJson(['allowed' => false]);
        $this->assertDatabaseHas('guest_sessions', ['id' => $session->id, 'geolocation_verified' => false]);
    }

    public function test_allows_when_venue_has_no_coordinates_configured(): void
    {
        $venue = Venue::factory()->create(['active' => true, 'latitude' => null, 'longitude' => null]);
        $token = $this->makeToken($venue);
        $session = $this->createGuestSession($venue, $token);

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$token}/verify-location", ['lat' => -23.5505, 'lng' => -46.6333])
            ->assertOk()
            ->assertJson(['allowed' => true, 'distance' => null]);
    }

    public function test_verify_location_without_session_returns_forbidden(): void
    {
        $venue = Venue::factory()->create(['active' => true, 'latitude' => -23.5505, 'longitude' => -46.6333]);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/verify-location", ['lat' => -23.5505, 'lng' => -46.6333])
            ->assertStatus(403);
    }
}
