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

    public function test_standalone_service_locations_page_is_not_available(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get('/settings/service-locations')->assertNotFound();
        $this->post('/settings/service-locations', [
            'name' => 'Área VIP',
            'active' => true,
        ])->assertNotFound();
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

    public function test_location_is_scoped_to_tenant(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $otherVenue = Venue::factory()->create(['active' => true]);

        $this->loginAs(UserRole::Owner, $venue);
        ServiceLocation::factory()->create(['venue_id' => $venue->id, 'name' => 'Mine']);
        ServiceLocation::factory()->create(['venue_id' => $otherVenue->id, 'name' => 'OtherLocation']);

        $this->get(route('settings.attendance-channels.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('serviceLocations', fn ($locations) => collect($locations)->every(
                    fn ($location) => $location['id'] !== null && ($location['name'] ?? null) !== 'OtherLocation'
                ))
            );
    }

    public function test_attendant_cannot_manage_service_locations(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Attendant, $venue);
        $location = ServiceLocation::factory()->create(['venue_id' => $venue->id]);

        $this->post(route('settings.service-locations.qr', $location->id))->assertForbidden();
    }

    public function test_locations_table_does_not_have_a_type_column(): void
    {
        $this->loginAs(UserRole::Owner);

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

        $this->from(route('settings.attendance-channels.index'))
            ->post(route('settings.service-locations.qr', $location->id))
            ->assertRedirect(route('settings.attendance-channels.index'))
            ->assertSessionHas('success');

        $location->refresh();

        $this->assertNotNull($location->qr_token);
    }

    public function test_generating_qr_from_attendance_channels_stays_on_that_page(): void
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
            'qr_token' => null,
        ]);

        $this->from(route('settings.attendance-channels.index'))
            ->post(route('settings.service-locations.qr', $location->id))
            ->assertRedirect(route('settings.attendance-channels.index'))
            ->assertSessionHas('success');

        $location->refresh();

        $this->assertNotNull($location->qr_token);
    }

    public function test_owner_can_preview_qr_png_when_token_exists(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'qr_token' => 'abc123token',
        ]);

        $response = $this->get(route('settings.service-locations.qr-png', $location->id));

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('content-type'));
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_owner_can_download_qr_png(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'qr_token' => 'abc123token',
        ]);

        $response = $this->get(route('settings.service-locations.qr-png', [
            'location' => $location->id,
            'download' => 1,
        ]));

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_owner_can_download_qr_svg(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'qr_token' => 'abc123token',
        ]);

        $response = $this->get(route('settings.service-locations.qr-svg', [
            'location' => $location->id,
            'download' => 1,
        ]));

        $response->assertOk();
        $this->assertSame('image/svg+xml', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_qr_png_returns_not_found_when_token_is_missing(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'qr_token' => null,
        ]);

        $this->get(route('settings.service-locations.qr-png', $location->id))->assertNotFound();
    }

    public function test_owner_can_download_qr_pdf(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $location = ServiceLocation::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas 1',
            'qr_token' => 'abc123token',
        ]);

        $response = $this->get(route('settings.service-locations.qr-pdf', [
            'location' => $location->id,
            'download' => 1,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_attendance_channels_index_includes_location_qr_token(): void
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
            'qr_token' => 'abc123',
            'active' => true,
        ]);

        $this->get(route('settings.attendance-channels.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/AttendanceChannels')
                ->where('serviceLocations.0.id', $location->id)
                ->where('serviceLocations.0.qr_token', 'abc123')
                ->where('serviceLocations.0.active', true)
            );
    }

    public function test_owner_can_inactivate_a_channel_location(): void
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
            'active' => true,
        ]);

        $this->put(route('settings.service-locations.update', $location->id), [
            'name' => 'Mesas 1',
            'active' => false,
            'default_attendance_channel_id' => $channel->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('service_locations', [
            'id' => $location->id,
            'active' => false,
        ]);
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
            'source' => 'several',
            'default_attendance_channel_id' => $channel->id,
        ])
            ->assertRedirect()
            ->assertSessionHas('location_create_result', function (array $result): bool {
                return $result['source'] === 'several'
                    && $result['created'] === 2
                    && $result['skipped'] === 1
                    && $result['created_names'] === ['Mesas 1', 'Mesas 3']
                    && $result['skipped_names'] === ['Mesas 2'];
            });

        $this->assertDatabaseHas('service_locations', ['venue_id' => $venue->id, 'name' => 'Mesas 1']);
        $this->assertDatabaseHas('service_locations', ['venue_id' => $venue->id, 'name' => 'Mesas 3']);
        $this->assertDatabaseCount('service_locations', 3);
    }

    public function test_location_range_reports_when_every_name_already_exists(): void
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
            'source' => 'one',
            'default_attendance_channel_id' => $channel->id,
        ])
            ->assertRedirect()
            ->assertSessionHas('location_create_result', function (array $result): bool {
                return $result['source'] === 'one'
                    && $result['created'] === 0
                    && $result['skipped_names'] === ['Mesas 1'];
            });

        $this->assertDatabaseCount('service_locations', 1);
    }

    public function test_location_range_requires_a_number(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Balcão',
        ]);

        $this->post(route('settings.service-locations.range'), [
            'from' => null,
            'to' => null,
            'default_attendance_channel_id' => $channel->id,
        ])->assertSessionHasErrors(['from', 'to']);

        $this->assertDatabaseMissing('service_locations', [
            'venue_id' => $venue->id,
            'name' => 'Balcão',
        ]);
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
            ->assertSessionHasErrors(['location' => 'Cannot delete this location while an attendance is open. Close the attendance first.']);

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
            ->assertSessionHasErrors(['locations' => 'Cannot delete these locations while an attendance is open. Close the open attendances first.']);

        $this->assertDatabaseHas('service_locations', ['name' => 'Mesas 1']);
        $this->assertDatabaseHas('service_locations', ['name' => 'Mesas 2']);
    }
}
