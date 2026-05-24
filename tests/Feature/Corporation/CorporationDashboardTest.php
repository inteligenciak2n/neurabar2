<?php

namespace Tests\Feature\Corporation;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorporationDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_corporation_dashboard(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('corporation.dashboard'))->assertOk();
    }

    public function test_dashboard_shows_venues_for_same_corporation(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $anotherVenue = Venue::factory()->create(['corporation_id' => $venue->corporation_id]);
        $outsideVenue = Venue::factory()->create();

        $response = $this->get(route('corporation.dashboard'));
        $venueIds = collect($response->original->getData()['page']['props']['venues'])->pluck('id')->toArray();

        $this->assertContains($venue->id, $venueIds);
        $this->assertContains($anotherVenue->id, $venueIds);
        $this->assertNotContains($outsideVenue->id, $venueIds);
    }

    public function test_owner_can_create_venue(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('corporation.venues.store'), [
            'name' => 'New Branch',
            'timezone' => 'America/Sao_Paulo',
        ])->assertRedirect(route('corporation.venues.index'));

        $this->assertDatabaseHas('venues', ['name' => 'New Branch', 'corporation_id' => $venue->corporation_id]);
    }

    public function test_owner_cannot_edit_venue_from_other_corporation(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $otherVenue = Venue::factory()->create();

        $this->get(route('corporation.venues.edit', $otherVenue->id))->assertForbidden();
    }
}
