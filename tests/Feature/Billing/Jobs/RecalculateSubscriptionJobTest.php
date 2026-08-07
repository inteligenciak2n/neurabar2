<?php

namespace Tests\Feature\Billing\Jobs;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Jobs\Billing\RecalculateSubscriptionJob;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueSubscription;
use App\Services\Billing\SubscriptionCalculator;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RecalculateSubscriptionJobTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_recalculates_venue_subscription_for_given_period(): void
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
        ]);

        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporation->subscription->id,
            'base_value' => 9990,
            'modules_value' => 0,
            'total_value' => 0,
            'status' => SubscriptionStatus::Active,
        ]);

        (new RecalculateSubscriptionJob($venue, '2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venue->id,
            'base_value' => 9990,
            'total_value' => 9990,
        ]);
    }

    public function test_uses_current_period_when_not_provided(): void
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
        ]);

        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporation->subscription->id,
            'base_value' => 4990,
            'modules_value' => 0,
            'total_value' => 0,
            'status' => SubscriptionStatus::Active,
        ]);

        (new RecalculateSubscriptionJob($venue))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venue->id,
            'base_value' => 4990,
            'total_value' => 4990,
        ]);
    }
}
