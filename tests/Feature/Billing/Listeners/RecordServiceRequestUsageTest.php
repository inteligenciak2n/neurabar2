<?php

namespace Tests\Feature\Billing\Listeners;

use App\Enums\ModuleCode;
use App\Enums\ServiceRequestType;
use App\Events\Orders\ServiceRequestCreated;
use App\Listeners\Billing\RecordServiceRequestUsage;
use App\Models\Orders\ServiceRequest;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RecordServiceRequestUsageTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_records_usage_for_direct_waiter_when_type_is_message(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);

        $serviceRequest = ServiceRequest::factory()->create([
            'venue_id' => $venue->id,
            'type' => ServiceRequestType::Message,
        ]);

        (new RecordServiceRequestUsage)->handle(new ServiceRequestCreated($serviceRequest));

        $this->assertDatabaseHas('venue_usage_records', [
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::DirectWaiter->value,
            'period' => now()->format('Y-m'),
            'quantity' => 1,
        ]);
    }

    public function test_does_not_record_usage_for_call_to_order_requests(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);

        $serviceRequest = ServiceRequest::factory()->callToOrder()->create([
            'venue_id' => $venue->id,
        ]);

        (new RecordServiceRequestUsage)->handle(new ServiceRequestCreated($serviceRequest));

        $this->assertDatabaseCount('venue_usage_records', 0);
    }

    public function test_does_not_record_usage_for_checkout_requests(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);

        $serviceRequest = ServiceRequest::factory()->checkout()->create([
            'venue_id' => $venue->id,
        ]);

        (new RecordServiceRequestUsage)->handle(new ServiceRequestCreated($serviceRequest));

        $this->assertDatabaseCount('venue_usage_records', 0);
    }
}
