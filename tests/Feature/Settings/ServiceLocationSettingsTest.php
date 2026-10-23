<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;
use App\Support\OperationalConnection;
use Illuminate\Support\Facades\Schema;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ServiceLocationSettingsTest extends TestCase
{
    use RefreshAllDatabases;

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
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('service_locations', [
            'venue_id' => $venue->id,
            'name' => 'Mesa 10',
        ]);
    }

    public function test_owner_can_update_service_location(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $location = ServiceLocation::factory()->create(['venue_id' => $venue->id]);

        $this->put(route('settings.service-locations.update', $location->id), [
            'name' => 'Área VIP',
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('service_locations', [
            'id' => $location->id,
            'name' => 'Área VIP',
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

    public function test_location_name_is_required(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.service-locations.store'), [
            'name' => '',
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

    public function test_index_does_not_expose_location_types(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.service-locations.index'))
            ->assertInertia(fn ($page) => $page->missing('locationTypes'));
    }

    public function test_store_creates_a_location_without_a_type(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.service-locations.store'), [
            'name' => 'Mesa 3',
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('service_locations', [
            'venue_id' => $venue->id,
            'name' => 'Mesa 3',
        ]);
        $this->assertFalse(Schema::connection(OperationalConnection::current())->hasColumn('service_locations', 'type'));
    }

    public function test_owner_can_generate_qr_token_for_service_location(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'qr_token' => null,
        ]);

        $this->post(route('settings.service-locations.qr', $location->id))
            ->assertRedirect(route('settings.service-locations.index'))
            ->assertSessionHas('success');

        $location->refresh();

        $this->assertNotNull($location->qr_token);
    }

    public function test_owner_can_create_a_prefixed_location_range_for_a_channel(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
        ]);

        $this->post(route('settings.service-locations.range'), [
            'from' => 1,
            'to' => 3,
            'default_attendance_channel_id' => $channel->id,
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Locations created successfully');

        $this->assertDatabaseHas('service_locations', [
            'venue_id' => $venue->id,
            'name' => 'Mesas 1',
            'default_attendance_channel_id' => $channel->id,
        ]);
        $this->assertDatabaseHas('service_locations', ['venue_id' => $venue->id, 'name' => 'Mesas 2']);
        $this->assertDatabaseHas('service_locations', ['venue_id' => $venue->id, 'name' => 'Mesas 3']);
    }

    public function test_location_range_skips_existing_names(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
        ]);
        ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 2',
            'default_attendance_channel_id' => $channel->id,
        ]);

        $this->post(route('settings.service-locations.range'), [
            'from' => 1,
            'to' => 3,
            'default_attendance_channel_id' => $channel->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('service_locations', ['venue_id' => $venue->id, 'name' => 'Mesas 1']);
        $this->assertDatabaseHas('service_locations', ['venue_id' => $venue->id, 'name' => 'Mesas 3']);
        $this->assertDatabaseCount('service_locations', 3);
    }

    public function test_location_range_fails_when_every_name_already_exists(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
        ]);
        ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 1',
            'default_attendance_channel_id' => $channel->id,
        ]);

        $this->post(route('settings.service-locations.range'), [
            'from' => 1,
            'to' => 1,
            'default_attendance_channel_id' => $channel->id,
        ])->assertSessionHasErrors(['from' => 'Every location in this range already exists.']);
    }

    public function test_owner_can_delete_a_channel_location(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
        ]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 1',
            'default_attendance_channel_id' => $channel->id,
        ]);

        $this->delete(route('settings.service-locations.destroy', $location->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('service_locations', ['id' => $location->id]);
    }

    public function test_cannot_delete_a_location_in_use_on_an_open_attendance(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'default_attendance_channel_id' => $channel->id,
        ]);

        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'service_location_id' => $location->id,
        ]);

        $this->delete(route('settings.service-locations.destroy', $location->id))
            ->assertSessionHasErrors(['location' => 'This location is in use on an open attendance.']);

        $this->assertDatabaseHas('service_locations', ['id' => $location->id]);
    }

    public function test_owner_can_delete_all_locations_for_a_channel(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
        ]);
        $otherChannel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Balcão',
        ]);
        ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 1',
            'default_attendance_channel_id' => $channel->id,
        ]);
        ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 2',
            'default_attendance_channel_id' => $channel->id,
        ]);
        $kept = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Balcão 1',
            'default_attendance_channel_id' => $otherChannel->id,
        ]);

        $this->delete(route('settings.attendance-channels.locations.destroy', $channel))
            ->assertRedirect()
            ->assertSessionHas('success', 'Locations deleted.');

        $this->assertDatabaseMissing('service_locations', ['venue_id' => $venue->id, 'name' => 'Mesas 1']);
        $this->assertDatabaseMissing('service_locations', ['venue_id' => $venue->id, 'name' => 'Mesas 2']);
        $this->assertDatabaseHas('service_locations', ['id' => $kept->id]);
    }

    public function test_cannot_delete_all_channel_locations_when_one_is_in_use(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
        ]);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 1',
            'default_attendance_channel_id' => $channel->id,
        ]);
        ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 2',
            'default_attendance_channel_id' => $channel->id,
        ]);

        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'service_location_id' => $location->id,
        ]);

        $this->delete(route('settings.attendance-channels.locations.destroy', $channel))
            ->assertSessionHasErrors(['locations' => 'Some locations are in use on an open attendance.']);

        $this->assertDatabaseHas('service_locations', ['name' => 'Mesas 1']);
        $this->assertDatabaseHas('service_locations', ['name' => 'Mesas 2']);
    }
}
