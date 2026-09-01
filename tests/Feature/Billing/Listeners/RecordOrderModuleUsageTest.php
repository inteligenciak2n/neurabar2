<?php

namespace Tests\Feature\Billing\Listeners;

use App\Enums\ModuleCode;
use App\Events\Orders\OrderPlaced;
use App\Listeners\Billing\RecordOrderModuleUsage;
use App\Models\Orders\DeliveryOrder;
use App\Models\Orders\Order;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RecordOrderModuleUsageTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_records_taker_usage_when_order_is_placed_by_staff(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['created_by' => $user->id]);
        $order->load('attendance');

        (new RecordOrderModuleUsage)->handle(new OrderPlaced($order));

        foreach ([ModuleCode::Taker, ModuleCode::DirectPrint] as $code) {
            $this->assertDatabaseHas('venue_usage_records', [
                'venue_id' => $order->attendance->venue_id,
                'module_code' => $code->value,
                'period' => now()->format('Y-m'),
                'quantity' => 1,
            ]);
        }

        $this->assertDatabaseMissing('venue_usage_records', [
            'venue_id' => $order->attendance->venue_id,
            'module_code' => ModuleCode::SelfOrder->value,
        ]);
    }

    public function test_it_records_self_order_usage_when_order_is_placed_by_a_guest(): void
    {
        $order = Order::factory()->create(['created_by' => null]);
        $order->load('attendance');

        (new RecordOrderModuleUsage)->handle(new OrderPlaced($order));

        foreach ([ModuleCode::SelfOrder, ModuleCode::DirectPrint] as $code) {
            $this->assertDatabaseHas('venue_usage_records', [
                'venue_id' => $order->attendance->venue_id,
                'module_code' => $code->value,
                'period' => now()->format('Y-m'),
                'quantity' => 1,
            ]);
        }

        $this->assertDatabaseMissing('venue_usage_records', [
            'venue_id' => $order->attendance->venue_id,
            'module_code' => ModuleCode::Taker->value,
        ]);
    }

    public function test_it_does_nothing_when_order_has_no_attendance(): void
    {
        $order = Order::factory()->create();
        $order->setRelation('attendance', null);

        (new RecordOrderModuleUsage)->handle(new OrderPlaced($order));

        $this->assertDatabaseCount('venue_usage_records', 0);
    }

    public function test_it_records_delivery_usage_when_attendance_has_a_delivery_order(): void
    {
        $order = Order::factory()->create(['created_by' => null]);
        $order->load('attendance');
        DeliveryOrder::factory()->create([
            'venue_id' => $order->attendance->venue_id,
            'attendance_id' => $order->attendance_id,
        ]);

        (new RecordOrderModuleUsage)->handle(new OrderPlaced($order));

        foreach ([ModuleCode::Delivery, ModuleCode::DirectPrint] as $code) {
            $this->assertDatabaseHas('venue_usage_records', [
                'venue_id' => $order->attendance->venue_id,
                'module_code' => $code->value,
                'period' => now()->format('Y-m'),
                'quantity' => 1,
            ]);
        }

        $this->assertDatabaseMissing('venue_usage_records', [
            'venue_id' => $order->attendance->venue_id,
            'module_code' => ModuleCode::SelfOrder->value,
        ]);
    }
}
