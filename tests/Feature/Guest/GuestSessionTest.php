<?php

namespace Tests\Feature\Guest;

use App\Models\GuestSession;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class GuestSessionTest extends TestCase
{
    use RefreshAllDatabases;

    private function makeToken(Venue $venue): string
    {
        $payload = ['v' => $venue->id];

        return rtrim(base64_encode(json_encode($payload)), '=');
    }

    public function test_store_creates_session_and_sets_cookie(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $response = $this->postJson("/g/{$token}/session", ['pin' => '1234']);

        $response->assertOk();
        $this->assertDatabaseCount('guest_sessions', 1);
        $this->assertNotNull($response->headers->getCookies());
    }

    /**
     * Create a session and return a properly-encrypted cookie value for subsequent requests.
     */
    private function createSessionAndGetCookie(Venue $venue, string $token, string $pin = '1234'): string
    {
        $this->postJson("/g/{$token}/session", ['pin' => $pin])->assertOk();

        $session = GuestSession::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->latest()
            ->first();

        return $session->guest_token;
    }

    public function test_store_returns_200_if_session_already_exists(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $encryptedCookie = $this->createSessionAndGetCookie($venue, $token);

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $encryptedCookie)
            ->postJson("/g/{$token}/session", ['pin' => '5678'])
            ->assertOk()
            ->assertJson(['already_exists' => true]);
    }

    public function test_pin_must_be_4_digits(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/session", ['pin' => 'abc'])
            ->assertUnprocessable();
    }

    public function test_verify_returns_valid_true_for_correct_pin(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $encryptedCookie = $this->createSessionAndGetCookie($venue, $token, '9876');

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $encryptedCookie)
            ->postJson("/g/{$token}/session/verify", ['pin' => '9876'])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_verify_returns_valid_false_for_wrong_pin(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $token = $this->makeToken($venue);

        $encryptedCookie = $this->createSessionAndGetCookie($venue, $token, '9876');

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $encryptedCookie)
            ->postJson("/g/{$token}/session/verify", ['pin' => '0000'])
            ->assertOk()
            ->assertJson(['valid' => false]);
    }
}
