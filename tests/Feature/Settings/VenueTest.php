<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class VenueTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_view_venue_settings(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.venue'))->assertOk();
    }

    public function test_general_manager_can_view_venue_settings(): void
    {
        $this->loginAs(UserRole::GeneralManager);

        $this->get(route('settings.venue'))->assertOk();
    }

    public function test_attendant_cannot_access_venue_settings(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('settings.venue'))->assertForbidden();
    }

    public function test_owner_can_update_venue(): void
    {
        $venue = Venue::factory()->create(['name' => 'Old Name', 'active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.venue.update'), [
            'name' => 'New Name',
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'name' => 'New Name']);
    }

    public function test_venue_name_is_required(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.venue.update'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }
}
