<?php

namespace Tests\Feature\Guest\Delivery;

use App\Enums\OrderStatus;
use App\Jobs\Delivery\ReleaseExpiredDeliveryOrderCodesJob;
use App\Models\Orders\DeliveryOrder;
use App\Models\Orders\Order;
use App\Models\Tenant\Venue;
use App\Services\TenantConnectionResolver;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ReleaseExpiredDeliveryOrderCodesJobTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_releases_only_codes_past_their_expiration(): void
    {
        $venue = Venue::factory()->create(['active' => true]);

        $expired = DeliveryOrder::factory()->create([
            'venue_id' => $venue->id,
            'code' => 123456,
            'code_expires_at' => now()->subMinute(),
        ]);
        $live = DeliveryOrder::factory()->create([
            'venue_id' => $venue->id,
            'code' => 654321,
            'code_expires_at' => now()->addHour(),
        ]);

        (new ReleaseExpiredDeliveryOrderCodesJob)->handle(app(TenantConnectionResolver::class));

        $this->assertDatabaseHas('delivery_orders', [
            'id' => $expired->id,
            'code' => null,
            'code_expires_at' => null,
        ]);
        $this->assertDatabaseHas('delivery_orders', [
            'id' => $live->id,
            'code' => 654321,
        ]);
    }

    public function test_a_released_code_can_be_issued_again(): void
    {
        $venue = Venue::factory()->create(['active' => true]);

        DeliveryOrder::factory()->create([
            'venue_id' => $venue->id,
            'code' => 111111,
            'code_expires_at' => now()->subMinute(),
        ]);

        (new ReleaseExpiredDeliveryOrderCodesJob)->handle(app(TenantConnectionResolver::class));

        DeliveryOrder::factory()->create([
            'venue_id' => $venue->id,
            'code' => 111111,
            'code_expires_at' => now()->addDay(),
        ]);

        $this->assertSame(1, DeliveryOrder::withoutGlobalScopes()->where('code', 111111)->count());
    }

    public function test_it_keeps_the_code_of_an_expired_order_that_is_still_in_flight(): void
    {
        $venue = Venue::factory()->create(['active' => true]);

        $inFlight = DeliveryOrder::factory()->create([
            'venue_id' => $venue->id,
            'code' => 222222,
            'code_expires_at' => now()->subMinute(),
        ]);
        Order::factory()->create(['attendance_id' => $inFlight->attendance_id, 'status' => OrderStatus::InPreparation]);

        $delivered = DeliveryOrder::factory()->create([
            'venue_id' => $venue->id,
            'code' => 333333,
            'code_expires_at' => now()->subMinute(),
        ]);
        Order::factory()->create(['attendance_id' => $delivered->attendance_id, 'status' => OrderStatus::Delivered]);

        (new ReleaseExpiredDeliveryOrderCodesJob)->handle(app(TenantConnectionResolver::class));

        $this->assertDatabaseHas('delivery_orders', ['id' => $inFlight->id, 'code' => 222222]);
        $this->assertDatabaseHas('delivery_orders', ['id' => $delivered->id, 'code' => null]);
    }
}
