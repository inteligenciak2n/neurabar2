<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceLocationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_service_locations(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.service-locations.index'))->assertOk();
    }

    public function test_owner_can_create_service_location(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.service-locations.store'), [
            'name' => 'Mesa 10',
            'type' => 'table',
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('service_locations', [
            'venue_id' => $venue->id,
            'name' => 'Mesa 10',
            'type' => 'table',
        ]);
    }

    public function test_owner_can_update_service_location(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $location = ServiceLocation::factory()->create(['venue_id' => $venue->id]);

        $this->put(route('settings.service-locations.update', $location->id), [
            'name' => 'Área VIP',
            'type' => 'area',
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('service_locations', [
            'id' => $location->id,
            'name' => 'Área VIP',
            'type' => 'area',
        ]);
    }

    public function test_owner_can_delete_service_location(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $location = ServiceLocation::factory()->create(['venue_id' => $venue->id]);

        $this->delete(route('settings.service-locations.destroy', $location->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('service_locations', ['id' => $location->id]);
    }

    public function test_invalid_type_returns_validation_error(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.service-locations.store'), [
            'name' => 'Área X',
            'type' => 'invalid_type',
        ])->assertSessionHasErrors('type');
    }

    public function test_location_name_is_required(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.service-locations.store'), [
            'name' => '',
            'type' => 'table',
        ])->assertSessionHasErrors('name');
    }

    public function test_location_is_scoped_to_tenant(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $otherVenue = Venue::factory()->create(['active' => true]);

        $this->loginAs(UserRole::Owner, $venue);
        ServiceLocation::factory()->create(['venue_id' => $otherVenue->id, 'name' => 'OtherLocation']);

        $response = $this->get(route('settings.service-locations.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('locations', fn ($locations) => collect($locations)->every(
                fn ($l) => $l['venue_id'] === $venue->id
            ))
        );
    }

    public function test_attendant_cannot_manage_service_locations(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('settings.service-locations.index'))->assertForbidden();
    }
}
