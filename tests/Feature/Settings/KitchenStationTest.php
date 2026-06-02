<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Settings\KitchenStation;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class KitchenStationTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_list_kitchen_stations(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.kitchen-stations.index'))->assertOk();
    }

    public function test_owner_can_create_kitchen_station(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.kitchen-stations.store'), [
            'name' => 'Grill Station',
            'sort_order' => 1,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('kitchen_stations', [
            'venue_id' => $venue->id,
            'name' => 'Grill Station',
        ]);
    }

    public function test_owner_can_update_kitchen_station(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $station = KitchenStation::factory()->create(['venue_id' => $venue->id]);

        $this->put(route('settings.kitchen-stations.update', $station->id), [
            'name' => 'Updated Station',
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('kitchen_stations', ['id' => $station->id, 'name' => 'Updated Station']);
    }

    public function test_owner_can_delete_kitchen_station_without_products(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $station = KitchenStation::factory()->create(['venue_id' => $venue->id]);

        $this->delete(route('settings.kitchen-stations.destroy', $station->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('kitchen_stations', ['id' => $station->id]);
    }

    public function test_station_name_is_required(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.kitchen-stations.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_attendant_cannot_manage_kitchen_stations(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('settings.kitchen-stations.index'))->assertForbidden();
    }
}
