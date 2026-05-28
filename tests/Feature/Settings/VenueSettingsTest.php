<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_general_settings(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.general'))->assertOk();
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
