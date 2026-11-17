<?php

namespace Tests\Feature\Settings;

use App\Enums\AttendanceChannelColor;
use App\Enums\UserRole;
use App\Models\Settings\AttendanceChannel;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class AttendanceChannelTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_list_attendance_channels(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.attendance-channels.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('channelColors', 8)
                ->where('channelColors', AttendanceChannelColor::values())
            );
    }

    public function test_owner_can_create_attendance_channel(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.attendance-channels.store'), [
            'name' => 'Balcão',
            'color' => '#ea580c',
            'description' => 'Pedidos feitos no balcão.',
            'is_trackable' => true,
            'requires_customer_identifier' => false,
            'requires_tab' => false,
            'uses_service_location' => true,
            'active' => true,
            'sort_order' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_channels', [
            'venue_id' => $venue->id,
            'name' => 'Balcão',
            'color' => '#ea580c',
            'description' => 'Pedidos feitos no balcão.',
            'requires_tab' => false,
            'uses_service_location' => true,
        ]);
    }

    public function test_owner_can_update_attendance_channel(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesas',
        ]);

        $this->put(route('settings.attendance-channels.update', $channel->id), [
            'name' => 'Delivery',
            'color' => '#16a34a',
            'description' => 'Pedidos para entrega.',
            'is_trackable' => true,
            'requires_customer_identifier' => false,
            'requires_tab' => true,
            'active' => true,
            'sort_order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_channels', [
            'id' => $channel->id,
            'name' => 'Mesas',
            'color' => '#16a34a',
            'description' => 'Pedidos para entrega.',
            'requires_tab' => true,
        ]);
    }

    public function test_channel_name_cannot_be_changed_after_creation(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Mesa',
        ]);

        $this->put(route('settings.attendance-channels.update', $channel->id), [
            'name' => 'Mesas',
            'description' => $channel->description,
            'is_trackable' => $channel->is_trackable,
            'requires_customer_identifier' => $channel->requires_customer_identifier,
            'requires_tab' => $channel->requires_tab,
            'active' => $channel->active,
            'sort_order' => $channel->sort_order,
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_channels', [
            'id' => $channel->id,
            'name' => 'Mesa',
        ]);
    }

    public function test_owner_can_delete_attendance_channel(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);

        $this->delete(route('settings.attendance-channels.destroy', $channel->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('attendance_channels', ['id' => $channel->id]);
    }

    public function test_owner_can_disable_service_location_on_a_channel(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'uses_service_location' => true,
        ]);

        $this->put(route('settings.attendance-channels.update', $channel->id), [
            'uses_service_location' => false,
            'is_trackable' => $channel->is_trackable,
            'requires_customer_identifier' => $channel->requires_customer_identifier,
            'requires_tab' => $channel->requires_tab,
            'active' => $channel->active,
            'sort_order' => $channel->sort_order,
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_channels', [
            'id' => $channel->id,
            'uses_service_location' => false,
        ]);
    }

    public function test_channel_color_must_be_a_hex_value(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->from(route('settings.attendance-channels.index'))
            ->post(route('settings.attendance-channels.store'), [
                'name' => 'Balcão',
                'color' => 'red',
            ])
            ->assertSessionHasErrors('color');
    }

    public function test_channel_color_must_be_one_of_the_palette(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->from(route('settings.attendance-channels.index'))
            ->post(route('settings.attendance-channels.store'), [
                'name' => 'Balcão',
                'color' => '#123456',
            ])
            ->assertSessionHasErrors('color');
    }

    public function test_channel_name_is_required(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.attendance-channels.store'), [
            'name' => '',
        ])->assertSessionHasErrors('name');
    }

    public function test_channel_is_scoped_to_tenant(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $otherVenue = Venue::factory()->create(['active' => true]);

        $this->loginAs(UserRole::Owner, $venue);
        AttendanceChannel::factory()->create(['venue_id' => $otherVenue->id, 'name' => 'OtherChannel']);

        $response = $this->get(route('settings.attendance-channels.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('channels', fn ($channels) => collect($channels)->every(
                fn ($c) => $c['venue_id'] === $venue->id
            ))
        );
    }

    public function test_owner_can_reorder_attendance_channels(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $first = AttendanceChannel::factory()->create(['venue_id' => $venue->id, 'name' => 'Mesas', 'sort_order' => 1]);
        $second = AttendanceChannel::factory()->create(['venue_id' => $venue->id, 'name' => 'Balcão', 'sort_order' => 2]);
        $third = AttendanceChannel::factory()->create(['venue_id' => $venue->id, 'name' => 'Delivery', 'sort_order' => 3]);

        $this->post(route('settings.attendance-channels.reorder'), ['ids' => [$third->id, $first->id, $second->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_channels', ['id' => $third->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('attendance_channels', ['id' => $first->id, 'sort_order' => 2]);
        $this->assertDatabaseHas('attendance_channels', ['id' => $second->id, 'sort_order' => 3]);
    }

    public function test_attendant_cannot_manage_attendance_channels(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('settings.attendance-channels.index'))->assertForbidden();
        $this->post(route('settings.attendance-channels.reorder'), ['ids' => ['00000000-0000-0000-0000-000000000001']])
            ->assertForbidden();
    }
}
