<?php

namespace Tests\Feature\Guest\Delivery;

use App\Jobs\Delivery\ReleaseExpiredDeliveryOrderCodesJob;
use App\Models\Orders\DeliveryOrder;
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
}
