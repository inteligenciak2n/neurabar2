<?php

namespace Tests\Feature\Kitchen;

use App\Enums\AttendanceStatus;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\UserRole;
use App\Models\Orders\Attendance;
use App\Models\Orders\DeliveryOrder;
use App\Models\Orders\Order;
use App\Models\Payment\Payment;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class AdvanceDeliveryOrderStatusTest extends TestCase
{
    use RefreshAllDatabases;

    private function enableKdsModule(Venue $venue): void
    {
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    public function test_ready_pickup_order_advances_directly_to_delivered_and_closes_attendance(): void
    {
        $venue = Venue::factory()->create();
        $this->enableKdsModule($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id, 'status' => 'ready']);
        Payment::factory()->create(['attendance_id' => $attendance->id]);
        DeliveryOrder::factory()->create(['venue_id' => $venue->id, 'attendance_id' => $attendance->id]);

        $this->put(route('kitchen.orders.advance-delivery-status', $order->id))->assertRedirect();

        $this->assertEquals('delivered', $order->fresh()->status->value);
        $this->assertEquals(AttendanceStatus::Closed, $attendance->fresh()->status);
    }

    public function test_ready_delivery_order_advances_to_out_for_delivery_then_delivered(): void
    {
        $venue = Venue::factory()->create();
        $this->enableKdsModule($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id, 'status' => 'ready']);
        Payment::factory()->create(['attendance_id' => $attendance->id]);
        DeliveryOrder::factory()->delivery()->create(['venue_id' => $venue->id, 'attendance_id' => $attendance->id]);

        $this->put(route('kitchen.orders.advance-delivery-status', $order->id));
        $this->assertEquals('out_for_delivery', $order->fresh()->status->value);
        $this->assertEquals(AttendanceStatus::Open, $attendance->fresh()->status);

        $this->put(route('kitchen.orders.advance-delivery-status', $order->id));
        $this->assertEquals('delivered', $order->fresh()->status->value);
        $this->assertEquals(AttendanceStatus::Closed, $attendance->fresh()->status);
    }

    public function test_advancing_an_order_without_a_delivery_order_fails(): void
    {
        $venue = Venue::factory()->create();
        $this->enableKdsModule($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id, 'status' => 'ready']);

        $this->put(route('kitchen.orders.advance-delivery-status', $order->id))->assertSessionHasErrors();
    }

    public function test_cannot_advance_order_that_is_not_ready(): void
    {
        $venue = Venue::factory()->create();
        $this->enableKdsModule($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id, 'status' => 'open']);
        DeliveryOrder::factory()->create(['venue_id' => $venue->id, 'attendance_id' => $attendance->id]);

        $this->put(route('kitchen.orders.advance-delivery-status', $order->id))->assertSessionHasErrors();
    }

    public function test_cannot_advance_an_order_belonging_to_another_venue(): void
    {
        $venue = Venue::factory()->create();
        $otherVenue = Venue::factory()->create();
        $this->enableKdsModule($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $otherVenue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id, 'status' => 'ready']);
        DeliveryOrder::factory()->create(['venue_id' => $otherVenue->id, 'attendance_id' => $attendance->id]);

        $this->put(route('kitchen.orders.advance-delivery-status', $order->id))->assertNotFound();
    }

    public function test_returns_forbidden_when_kds_module_is_inactive(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id, 'status' => 'ready']);
        DeliveryOrder::factory()->create(['venue_id' => $venue->id, 'attendance_id' => $attendance->id]);

        $this->put(route('kitchen.orders.advance-delivery-status', $order->id))->assertForbidden();
    }

    public function test_delivering_a_pickup_order_charges_the_payment_methods_chosen_at_checkout(): void
    {
        $venue = Venue::factory()->create();
        $this->enableKdsModule($venue);
        $this->loginAs(UserRole::Attendant, $venue);
        VenueSettings::factory()->create(['venue_id' => $venue->id, 'service_fee_percent' => 0]);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id, 'status' => 'ready']);
        $deliveryOrder = DeliveryOrder::factory()->create([
            'venue_id' => $venue->id,
            'attendance_id' => $attendance->id,
            'delivery_fee' => 0,
        ]);
        $deliveryOrder->paymentMethods()->create(['method' => 'cash', 'amount' => 40]);

        $this->put(route('kitchen.orders.advance-delivery-status', $order->id))->assertRedirect();

        $this->assertNotNull($attendance->fresh()->payment);
        $this->assertDatabaseHas('payment_items', ['method' => 'cash', 'amount' => 40]);
    }
}
