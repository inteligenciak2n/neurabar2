<?php

namespace Tests\Feature\Billing\Jobs;

use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\Billing\SuspendOverdueSubscriptionsJob;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use Carbon\Carbon;
use Tests\TestCase;

class SuspendOverdueSubscriptionsJobTest extends TestCase
{
    public function test_it_suspends_corporation_past_due_after_grace_period(): void
    {
        $subscription = CorporationSubscription::factory()->create([
            'status' => SubscriptionStatus::PastDue,
            'trial_ends_at' => Carbon::now()->subDays(10),
            'grace_period_days' => 3,
        ]);

        (new SuspendOverdueSubscriptionsJob)->handle();

        $this->assertDatabaseHas('corporation_subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Suspended->value,
        ]);
    }

    public function test_it_suspends_unified_corporation_with_overdue_invoice(): void
    {
        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'grace_period_days' => 3,
        ]);
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $subscription->id,
            'status' => SubscriptionStatus::Active,
        ]);
        CorporationInvoice::factory()->create([
            'corporation_id' => $corporation->id,
            'corporation_subscription_id' => $subscription->id,
            'status' => InvoiceStatus::Overdue,
            'due_date' => Carbon::now()->subDays(10),
        ]);

        (new SuspendOverdueSubscriptionsJob)->handle();

        $this->assertDatabaseHas('corporation_subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Suspended->value,
        ]);
        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venue->id,
            'status' => SubscriptionStatus::Suspended->value,
        ]);
    }

    public function test_it_suspends_only_overdue_venue_in_per_venue_mode(): void
    {
        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
            'grace_period_days' => 3,
        ]);
        $venueA = Venue::factory()->create(['corporation_id' => $corporation->id]);
        $venueB = Venue::factory()->create(['corporation_id' => $corporation->id]);

        $subscriptionA = VenueSubscription::factory()->create([
            'venue_id' => $venueA->id,
            'corporation_subscription_id' => $subscription->id,
            'status' => SubscriptionStatus::Active,
        ]);
        $subscriptionB = VenueSubscription::factory()->create([
            'venue_id' => $venueB->id,
            'corporation_subscription_id' => $subscription->id,
            'status' => SubscriptionStatus::Active,
        ]);

        VenueInvoice::factory()->create([
            'venue_id' => $venueA->id,
            'venue_subscription_id' => $subscriptionA->id,
            'status' => InvoiceStatus::Overdue,
            'due_date' => Carbon::now()->subDays(10),
        ]);

        (new SuspendOverdueSubscriptionsJob)->handle();

        $this->assertDatabaseHas('venue_subscriptions', [
            'id' => $subscriptionA->id,
            'status' => SubscriptionStatus::Suspended->value,
        ]);
        $this->assertDatabaseHas('venue_subscriptions', [
            'id' => $subscriptionB->id,
            'status' => SubscriptionStatus::Active->value,
        ]);
        $this->assertDatabaseHas('corporation_subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Active->value,
        ]);
    }
}
