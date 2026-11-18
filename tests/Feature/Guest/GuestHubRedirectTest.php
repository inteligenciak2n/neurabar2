<?php

namespace Tests\Feature\Guest;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\GuestSession;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class GuestHubRedirectTest extends TestCase
{
    use RefreshAllDatabases;

    private function makeToken(Venue $venue, ?ServiceLocation $location = null): string
    {
        $payload = ['v' => $venue->id];

        if ($location !== null) {
            $payload['l'] = $location->id;
        }

        return rtrim(base64_encode(json_encode($payload)), '=');
    }

    public function test_qr_hub_redirects_to_the_customer_table_menu(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesa 4',
        ]);
        $token = $this->makeToken($venue, $location);

        $this->get("/g/{$token}")
            ->assertRedirect(route('guest.menu', ['token' => $token]));
    }

    public function test_table_qr_menu_identifies_the_service_location_and_opens_a_session_without_a_pin(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesa 4',
        ]);
        $token = $this->makeToken($venue, $location);

        $this->get("/g/{$token}/menu")
            ->assertOk()
            ->assertCookie('guest_token')
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Menu')
                ->where('serviceLocation.id', $location->id)
                ->where('serviceLocation.name', 'Mesa 4')
            );

        $this->assertDatabaseHas('guest_sessions', [
            'venue_id' => $venue->id,
            'service_location_id' => $location->id,
            'pin' => null,
        ]);
    }

    public function test_reopening_the_same_table_qr_reuses_the_existing_session(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesa 4',
        ]);
        $token = $this->makeToken($venue, $location);

        $first = $this->get("/g/{$token}/menu")->assertOk()->assertCookie('guest_token');
        $cookie = $first->getCookie('guest_token', false)->getValue();

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $cookie)
            ->get("/g/{$token}/menu")
            ->assertOk();

        $this->assertSame(1, GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->count());
    }

    public function test_scanning_a_second_table_qr_creates_a_new_session_bound_to_the_new_table(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $firstLocation = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesa 4',
        ]);
        $secondLocation = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesa 8',
        ]);

        $first = $this->get('/g/'.$this->makeToken($venue, $firstLocation).'/menu')
            ->assertOk()
            ->assertCookie('guest_token');
        $firstCookie = $first->getCookie('guest_token', false)->getValue();

        $second = $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $firstCookie)
            ->get('/g/'.$this->makeToken($venue, $secondLocation).'/menu')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('serviceLocation.id', $secondLocation->id));
        $secondCookie = $second->getCookie('guest_token', false)->getValue();

        $this->assertNotSame($firstCookie, $secondCookie);

        $this->assertDatabaseHas('guest_sessions', [
            'venue_id' => $venue->id,
            'service_location_id' => $firstLocation->id,
            'guest_token' => $firstCookie,
        ]);
        $this->assertDatabaseHas('guest_sessions', [
            'venue_id' => $venue->id,
            'service_location_id' => $secondLocation->id,
            'guest_token' => $secondCookie,
        ]);
    }

    public function test_guest_orders_index_returns_orders_without_a_pin(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::SelfOrder->value,
            'status' => ModuleStatus::Active,
        ]);
        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::SelfOrder->value,
            'status' => ModuleStatus::Active,
        ]);
        $token = $this->makeToken($venue);

        $this->postJson("/g/{$token}/session", [])->assertOk();
        $session = GuestSession::withoutGlobalScopes()->where('venue_id', $venue->id)->latest()->first();

        $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->getJson("/g/{$token}/orders")
            ->assertOk()
            ->assertJson(['orders' => []]);
    }
}
