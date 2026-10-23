<?php

namespace Tests\Feature\Orders;

use App\Enums\UserRole;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\Tab;
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

        $response = $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($attendance);
        $response->assertRedirect(route('orders.take', $attendance));

        $this->assertDatabaseHas('attendances', [
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => null,
            'status' => 'open',
        ]);
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

        $response = $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'customer_identifier' => 'Table 7',
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($attendance);
        $response->assertRedirect(route('orders.take', $attendance));
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

        $response = $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'code' => ' c-042 ',
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($attendance);
        $response->assertRedirect(route('orders.take', $attendance));
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

    public function test_opening_attendance_without_tab_fails_when_channel_requires_it(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
        ]);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
        ])->assertSessionHasErrors('tab_id');
    }

    public function test_opening_attendance_with_tab_when_channel_requires_it(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '5',
        ]);

        $response = $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($attendance);
        $response->assertRedirect(route('orders.take', $attendance));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'tab_id' => $tab->id,
        ]);
    }

    public function test_cannot_open_attendance_with_a_tab_already_in_use(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '10',
        ]);

        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
        ]);

        $this->post(route('attendances.store'), [
            'attendance_channel_id' => $channel->id,
            'tab_id' => $tab->id,
        ])->assertSessionHasErrors('tab_id');
    }

    public function test_index_omits_tabs_that_are_already_open(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'requires_tab' => true,
        ]);
        $freeTab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '5',
        ]);
        $busyTab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => '10',
        ]);

        Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $busyTab->id,
        ]);

        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Attendances/Index')
                ->has('tabs', 1)
                ->where('tabs.0.id', $freeTab->id)
            );
    }

    public function test_index_includes_products_for_the_order_locator(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $channel = AttendanceChannel::factory()->create(['venue_id' => $venue->id]);
        $attendance = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
        ]);
        $product = Product::factory()->create(['name' => 'Suco de laranja']);
        $order = Order::factory()->create([
            'attendance_id' => $attendance->id,
            'order_number' => 12,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Attendances/Index')
                ->where('attendances.0.orders.0.order_number', 12)
                ->where('attendances.0.orders.0.items.0.product.name', 'Suco de laranja')
            );
    }

    public function test_shared_tab_can_be_opened_on_any_channel_that_requires_it(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Salão',
            'requires_tab' => true,
        ]);
        $otherChannel = AttendanceChannel::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Área externa',
            'requires_tab' => true,
        ]);
        $tab = Tab::factory()->create([
            'venue_id' => $venue->id,
            'code' => 'A15',
        ]);

        $response = $this->post(route('attendances.store'), [
            'attendance_channel_id' => $otherChannel->id,
            'tab_id' => $tab->id,
        ]);

        $attendance = Attendance::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($attendance);
        $response->assertRedirect(route('orders.take', $attendance));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'attendance_channel_id' => $otherChannel->id,
            'tab_id' => $tab->id,
        ]);
    }
}
