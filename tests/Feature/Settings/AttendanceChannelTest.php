<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Settings\AttendanceChannel;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_attendance_channels(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.attendance-channels.index'))->assertOk();
    }

    public function test_owner_can_create_attendance_channel(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.attendance-channels.store'), [
            'name' => 'Balcão',
            'is_trackable' => true,
            'requires_customer_identifier' => false,
            'active' => true,
            'sort_order' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_channels', [
            'venue_id' => $venue->id,
            'name' => 'Balcão',
        ]);
    }

    public function test_owner_can_update_attendance_channel(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);

        $this->put(route('settings.attendance-channels.update', $channel->id), [
            'name' => 'Delivery',
            'is_trackable' => true,
            'requires_customer_identifier' => false,
            'active' => true,
            'sort_order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_channels', [
            'id' => $channel->id,
            'name' => 'Delivery',
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

    public function test_attendant_cannot_manage_attendance_channels(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('settings.attendance-channels.index'))->assertForbidden();
    }
}
