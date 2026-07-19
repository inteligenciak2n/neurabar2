<?php

namespace Tests\Feature\Billing\Listeners;

use App\Enums\ModuleCode;
use App\Events\Orders\OrderPlaced;
use App\Listeners\Billing\RecordOrderModuleUsage;
use App\Models\Orders\Order;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RecordOrderModuleUsageTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_records_usage_for_relevant_modules_when_order_is_placed(): void
    {
        $order = Order::factory()->create();
        $order->load('attendance');

        (new RecordOrderModuleUsage)->handle(new OrderPlaced($order));

        foreach ([ModuleCode::Kds, ModuleCode::Taker, ModuleCode::DirectPrint] as $code) {
            $this->assertDatabaseHas('venue_usage_records', [
                'venue_id' => $order->attendance->venue_id,
                'module_code' => $code->value,
                'period' => now()->format('Y-m'),
                'quantity' => 1,
            ]);
        }
    }

    public function test_it_does_nothing_when_order_has_no_attendance(): void
    {
        $order = Order::factory()->create();
        $order->setRelation('attendance', null);

        (new RecordOrderModuleUsage)->handle(new OrderPlaced($order));

        $this->assertDatabaseCount('venue_usage_records', 0);
    }
}
