<?php

namespace Tests\Feature\Billing;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueSubscription;
use App\Services\Billing\BillingStatusService;
use Illuminate\Support\Facades\Cache;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class BillingStatusServiceTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_venue_without_corporation_subscription_is_blocked(): void
    {
        $venue = Venue::factory()->create();
        $venue->corporation->subscription()->delete();
        $venue->unsetRelation('corporation');

        $this->assertTrue(BillingStatusService::isBlocked($venue->fresh()));
    }

    public function test_missing_subscription_does_not_suspend_the_public_channel(): void
    {
        $venue = Venue::factory()->create();
        $venue->corporation->subscription()->delete();
        $venue->unsetRelation('corporation');

        $this->assertFalse(BillingStatusService::isSuspended($venue->fresh()));
    }

    public function test_suspended_unified_corporation_suspends_the_public_channel(): void
    {
        $venue = Venue::factory()->create();

        CorporationSubscription::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Suspended,
        ]);

        $this->assertTrue(BillingStatusService::isSuspended($venue->fresh()));
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

    public function test_caches_blocked_result_until_flushed(): void
    {
        Cache::flush();

        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
        ]);

        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);

        $this->assertFalse(BillingStatusService::isBlocked($venue));

        $subscription->update(['status' => SubscriptionStatus::Suspended]);

        $this->assertFalse(
            BillingStatusService::isBlocked($venue),
            'Resultado deve permanecer cacheado como não bloqueado'
        );

        BillingStatusService::flushBlockedCache($venue);

        $this->assertTrue(BillingStatusService::isBlocked($venue->fresh()));
    }
}
