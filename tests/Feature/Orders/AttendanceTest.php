<?php

namespace Tests\Feature\Orders;

use App\Enums\UserRole;
use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_attendant_can_open_attendance(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
        ])->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', ['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id, 'status' => 'open']);
    }

    public function test_opening_attendance_without_identifier_fails_when_channel_requires_it(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_customer_identifier' => true,
        ]);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
        ])->assertSessionHasErrors('customer_identifier');
    }

    public function test_opening_attendance_with_identifier_when_channel_requires_it(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_customer_identifier' => true,
        ]);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'customer_identifier' => 'Table 7',
        ])->assertRedirect(route('attendances.index'));
    }

    public function test_index_returns_only_tenant_attendances(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        Attendance::factory()->open()->create(['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id]);

        $otherVenue = Venue::factory()->create();
        $otherChannel = AttendanceChannel::factory()->create(['venue_id' => $otherVenue->id]);
        Attendance::factory()->open()->create(['venue_id' => $otherVenue->id, 'attendance_channel_id' => $otherChannel->id]);

        $response = $this->get(route('attendances.index'))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Attendances/Index')
            ->has('attendances', 1)
        );
    }

    public function test_other_tenant_attendance_is_not_accessible(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $otherVenue = Venue::factory()->create();
        $otherChannel = AttendanceChannel::factory()->create(['venue_id' => $otherVenue->id]);
        $otherAttendance = Attendance::factory()->open()->create(['venue_id' => $otherVenue->id, 'attendance_channel_id' => $otherChannel->id]);

        $this->get(route('attendances.show', $otherAttendance->id))->assertNotFound();
    }

    public function test_attendant_can_open_attendance_with_a_tab_card_code(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'code' => ' c-042 ',
        ])->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', ['venue_id' => $venue->id, 'code' => 'C-042', 'status' => 'open']);
    }

    public function test_tab_card_code_cannot_be_used_by_two_open_attendances(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        Attendance::factory()->open()->create(['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id, 'code' => '42']);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'code' => '42',
        ])->assertSessionHasErrors('code');

        $this->assertSame(1, Attendance::withoutGlobalScopes()->where('code', '42')->count());
    }

    public function test_tab_card_code_is_available_again_after_the_attendance_closes(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        Attendance::factory()->closed()->create(['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id, 'code' => '42']);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'code' => '42',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, Attendance::withoutGlobalScopes()->open()->where('code', '42')->count());
    }

    public function test_tab_card_code_is_scoped_per_venue(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        $otherVenue = Venue::factory()->create();
        Attendance::factory()->open()->create(['venue_id' => $otherVenue->id, 'code' => '42']);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'code' => '42',
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_updating_an_attendance_can_keep_its_own_code_but_not_take_another(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        $mine = Attendance::factory()->open()->create(['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id, 'code' => '10']);
        Attendance::factory()->open()->create(['venue_id' => $venue->id, 'attendance_channel_id' => $channel->id, 'code' => '20']);

        $this->put(route('attendances.update', $mine->id), ['code' => '10', 'notes' => 'ok'])
            ->assertSessionDoesntHaveErrors();

        $this->put(route('attendances.update', $mine->id), ['code' => '20'])
            ->assertSessionHasErrors('code');
    }
}
