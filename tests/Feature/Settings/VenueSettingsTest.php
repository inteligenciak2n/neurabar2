<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class VenueSettingsTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_view_general_settings(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.general'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/General')
                ->has('settings')
                ->has('venue.require_table')
                ->has('venue.require_geolocation')
                ->has('venue.latitude')
            );
    }

    public function test_owner_can_update_general_settings(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.general.update'), [
            'cover_charge' => '10.00',
            'service_fee_percent' => '10.00',
            'table_count' => 20,
        ])->assertRedirect();

        $this->assertDatabaseHas('venue_settings', [
            'venue_id' => $venue->id,
            'table_count' => 20,
        ]);
    }

    public function test_owner_can_update_operational_requirements_and_geolocation(): void
    {
        $venue = Venue::factory()->create([
            'active' => true,
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
            'require_geolocation' => false,
            'latitude' => null,
            'longitude' => null,
        ]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.general.update'), [
            'cover_charge' => '10.00',
            'service_fee_percent' => '10.00',
            'table_count' => 20,
            'require_table' => true,
            'require_tab' => true,
            'require_location' => false,
            'latitude' => '-23.5505',
            'longitude' => '-46.6333',
            'require_geolocation' => true,
        ])->assertRedirect();

        $venue->refresh();

        $this->assertTrue($venue->require_table);
        $this->assertTrue($venue->require_tab);
        $this->assertFalse($venue->require_location);
        $this->assertTrue($venue->require_geolocation);
        $this->assertEquals(-23.5505, (float) $venue->latitude);
        $this->assertEquals(-46.6333, (float) $venue->longitude);
    }

    public function test_service_fee_percent_cannot_exceed_100(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.general.update'), [
            'service_fee_percent' => '150',
        ])->assertSessionHasErrors('service_fee_percent');
    }

    public function test_attendant_cannot_access_general_settings(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('settings.general'))->assertForbidden();
    }
}
