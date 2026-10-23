<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\Tab;
use App\Models\Settings\TabSeries;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ChannelTabTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_create_a_tab(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
        ]);

        $this->post(route('settings.tabs.store'), [
            'code' => 'a1',
            'notes' => 'Comandas do salão',
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Tabs created successfully');

        $this->assertDatabaseHas('tabs', [
            'venue_id' => $venue->id,
            'code' => 'A1',
        ]);
        $this->assertDatabaseHas('tab_series', [
            'venue_id' => $venue->id,
            'prefix' => 'A',
            'notes' => 'Comandas do salão',
        ]);
    }

    public function test_owner_can_create_a_numeric_tab_range(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '2',
        ]);

        $this->post(route('settings.tabs.range'), [
            'from' => 1,
            'to' => 3,
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Tabs created successfully');

        $this->assertDatabaseHas('tabs', ['venue_id' => $venue->id, 'code' => '1']);
        $this->assertDatabaseHas('tabs', ['venue_id' => $venue->id, 'code' => '2']);
        $this->assertDatabaseHas('tabs', ['venue_id' => $venue->id, 'code' => '3']);
        $this->assertDatabaseCount('tabs', 3);
    }

    public function test_owner_can_create_a_prefixed_tab_range_with_notes(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.tabs.range'), [
            'prefix' => 'a',
            'from' => 1,
            'to' => 2,
            'notes' => 'Comandas do salão',
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Tabs created successfully');

        $this->assertDatabaseHas('tabs', ['venue_id' => $venue->id, 'code' => 'A1']);
        $this->assertDatabaseHas('tabs', ['venue_id' => $venue->id, 'code' => 'A2']);
        $this->assertDatabaseHas('tab_series', [
            'venue_id' => $venue->id,
            'prefix' => 'A',
            'notes' => 'Comandas do salão',
        ]);
    }

    public function test_alphanumeric_prefix_keeps_trailing_digits_and_uses_a_hyphen(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.tabs.range'), [
            'prefix' => 'jiosque3-',
            'from' => 1,
            'to' => 2,
            'notes' => 'Quiosque 3',
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Tabs created successfully');

        $this->assertDatabaseHas('tabs', ['venue_id' => $venue->id, 'code' => 'JIOSQUE3-1']);
        $this->assertDatabaseHas('tabs', ['venue_id' => $venue->id, 'code' => 'JIOSQUE3-2']);
        $this->assertDatabaseMissing('tabs', ['venue_id' => $venue->id, 'code' => 'JIOSQUE3']);
        $this->assertDatabaseHas('tab_series', [
            'venue_id' => $venue->id,
            'prefix' => 'JIOSQUE3',
            'notes' => 'Quiosque 3',
        ]);
    }

    public function test_prefix_ending_with_a_digit_does_not_collide_with_that_tab_code(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => 'CASA1',
        ]);

        $this->post(route('settings.tabs.range'), [
            'prefix' => 'casa1',
            'from' => 1,
            'to' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('tabs', ['venue_id' => $venue->id, 'code' => 'CASA1']);
        $this->assertDatabaseHas('tabs', ['venue_id' => $venue->id, 'code' => 'CASA1-1']);
        $this->assertDatabaseCount('tabs', 2);
    }

    public function test_tab_code_must_be_unique_in_the_venue(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '15',
        ]);

        $this->post(route('settings.tabs.store'), [
            'code' => '15',
        ])->assertSessionHasErrors(['code' => 'This tab code is already in use.']);
    }

    public function test_tab_code_rejects_symbols(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.tabs.store'), [
            'code' => '15-A',
        ])->assertSessionHasErrors('code');
    }

    public function test_owner_can_delete_a_tab(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '7',
        ]);

        $this->delete(route('settings.tabs.destroy', $tab))
            ->assertRedirect();

        $this->assertDatabaseMissing('tabs', ['id' => $tab->id]);
    }

    public function test_cannot_delete_a_tab_in_use_on_an_open_attendance(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '8',
        ]);

        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
        ]);

        $this->delete(route('settings.tabs.destroy', $tab))
            ->assertSessionHasErrors('tab');

        $this->assertDatabaseHas('tabs', ['id' => $tab->id]);
    }

    public function test_owner_can_delete_all_tabs_in_a_series(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $series = TabSeries::factory()->create([
            'venue_id' => $venue->id,
            'prefix' => 'BAR',
        ]);
        Tab::factory()->create([
            'venue_id' => $venue->id,
            'tab_series_id' => $series->id,
            'code' => 'BAR1',
        ]);
        Tab::factory()->create([
            'venue_id' => $venue->id,
            'tab_series_id' => $series->id,
            'code' => 'BAR2',
        ]);

        $this->delete(route('settings.tab-series.destroy', $series))
            ->assertRedirect();

        $this->assertDatabaseMissing('tabs', ['venue_id' => $venue->id, 'code' => 'BAR1']);
        $this->assertDatabaseMissing('tabs', ['venue_id' => $venue->id, 'code' => 'BAR2']);
        $this->assertDatabaseMissing('tab_series', ['id' => $series->id]);
    }

    public function test_cannot_delete_a_series_when_a_tab_is_in_use(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        $series = TabSeries::factory()->create([
            'venue_id' => $venue->id,
            'prefix' => 'BAR',
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'tab_series_id' => $series->id,
            'code' => 'BAR1',
        ]);
        Tab::factory()->create([
            'venue_id' => $venue->id,
            'tab_series_id' => $series->id,
            'code' => 'BAR2',
        ]);

        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
        ]);

        $this->delete(route('settings.tab-series.destroy', $series))
            ->assertSessionHasErrors(['tabs' => 'Some tabs in this prefix are in use on an open attendance.']);

        $this->assertDatabaseHas('tabs', ['id' => $tab->id]);
        $this->assertDatabaseHas('tab_series', ['id' => $series->id]);
    }

    public function test_range_fails_when_every_code_already_exists(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '1',
        ]);

        $this->post(route('settings.tabs.range'), [
            'from' => 1,
            'to' => 1,
        ])->assertSessionHasErrors(['from' => 'Every code in this range already exists.']);
    }

    public function test_attendant_cannot_manage_tabs(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Attendant, $venue);

        $this->post(route('settings.tabs.store'), [
            'code' => '1',
        ])->assertForbidden();
    }

    public function test_index_includes_shared_tab_series_when_a_channel_requires_tabs(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => 'VIP',
        ]);

        $this->get(route('settings.attendance-channels.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/AttendanceChannels')
                ->has('channels', 1)
                ->has('tabSeries', 1)
                ->where('tabSeries.0.tabs.0.id', $tab->id)
                ->where('tabSeries.0.tabs.0.code', 'VIP')
            );
    }

    public function test_owner_can_update_series_notes(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $series = TabSeries::factory()->create([
            'venue_id' => $venue->id,
            'prefix' => 'A',
            'notes' => null,
        ]);

        $this->put(route('settings.tab-series.update', $series), [
            'notes' => 'Área externa',
        ])->assertRedirect();

        $this->assertDatabaseHas('tab_series', [
            'id' => $series->id,
            'notes' => 'Área externa',
        ]);
    }
}
