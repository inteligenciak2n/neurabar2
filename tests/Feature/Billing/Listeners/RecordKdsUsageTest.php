<?php

namespace Tests\Feature\Billing\Listeners;

use App\Enums\ModuleCode;
use App\Events\Kitchen\ItemStatusUpdated;
use App\Listeners\Billing\RecordKdsUsage;
use App\Models\Orders\OrderItem;
use App\Models\Settings\PreparationStatus;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RecordKdsUsageTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_records_kds_usage_when_item_reaches_final_status(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);

        $finalStatus = PreparationStatus::factory()->create([
            'venue_id' => $venue->id,
            'is_final' => true,
        ]);

        $item = OrderItem::factory()->create([
            'preparation_status_id' => $finalStatus->id,
        ]);
        $item->load(['order.attendance', 'preparationStatus']);

        (new RecordKdsUsage)->handle(new ItemStatusUpdated($item));

        $this->assertDatabaseHas('venue_usage_records', [
            'venue_id' => $item->order->attendance->venue_id,
            'module_code' => ModuleCode::Kds->value,
            'period' => now()->format('Y-m'),
            'quantity' => 1,
        ]);
    }

    public function test_does_not_record_when_status_is_not_final(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);

        $nonFinalStatus = PreparationStatus::factory()->create([
            'venue_id' => $venue->id,
            'is_final' => false,
        ]);

        $item = OrderItem::factory()->create([
            'preparation_status_id' => $nonFinalStatus->id,
        ]);
        $item->load(['order.attendance', 'preparationStatus']);

        (new RecordKdsUsage)->handle(new ItemStatusUpdated($item));

        $this->assertDatabaseCount('venue_usage_records', 0);
    }

    public function test_does_not_record_when_order_has_no_attendance(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);

        $finalStatus = PreparationStatus::factory()->create([
            'venue_id' => $venue->id,
            'is_final' => true,
        ]);

        $item = OrderItem::factory()->create([
            'preparation_status_id' => $finalStatus->id,
        ]);
        $item->load('preparationStatus');
        $item->setRelation('order', $item->order->setRelation('attendance', null));

        (new RecordKdsUsage)->handle(new ItemStatusUpdated($item));

        $this->assertDatabaseCount('venue_usage_records', 0);
    }
}
