<?php

namespace Tests\Feature\Billing;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueSubscription;
use App\Services\Billing\BillingStatusService;
use Tests\TestCase;

class BillingStatusServiceTest extends TestCase
{
    public function test_venue_without_corporation_subscription_is_blocked(): void
    {
        $venue = Venue::factory()->create();
        $venue->corporation->subscription()->delete();
        $venue->unsetRelation('corporation');

        $this->assertTrue(BillingStatusService::isBlocked($venue->fresh()));
    }

    public function test_unified_corporation_suspended_blocks_venue(): void
    {
        $venue = Venue::factory()->create();
        $corporation = $venue->corporation;

        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Suspended,
        ]);

        $this->assertTrue(BillingStatusService::isBlocked($venue->fresh()));
    }

    public function test_unified_corporation_past_due_is_not_blocked(): void
    {
        $venue = Venue::factory()->create();
        $corporation = $venue->corporation;

        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::PastDue,
        ]);

        $this->assertFalse(BillingStatusService::isBlocked($venue->fresh()));
        $this->assertTrue(BillingStatusService::isInGracePeriod($venue->fresh()));
    }

    public function test_per_venue_suspended_blocks_only_that_venue(): void
    {
        $venue = Venue::factory()->create();
        $corporation = $venue->corporation;

        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
        ]);

        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporation->subscription->id,
            'status' => SubscriptionStatus::Suspended,
        ]);

        $this->assertTrue(BillingStatusService::isBlocked($venue->fresh()));
    }
}
