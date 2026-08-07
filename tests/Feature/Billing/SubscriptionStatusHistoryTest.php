<?php

namespace Tests\Feature\Billing;

use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\Billing\SuspendOverdueSubscriptionsJob;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\SubscriptionStatusHistory;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use Illuminate\Support\Facades\Notification;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class SubscriptionStatusHistoryTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_records_every_status_transition(): void
    {
        $subscription = CorporationSubscription::factory()->create([
            'status' => SubscriptionStatus::Trial,
        ]);

        $subscription->update(['status' => SubscriptionStatus::Active]);

        $history = SubscriptionStatusHistory::query()
            ->where('subscription_id', $subscription->id)
            ->get();

        $this->assertCount(1, $history);
        $this->assertSame(SubscriptionStatus::Trial, $history->first()->from_status);
        $this->assertSame(SubscriptionStatus::Active, $history->first()->to_status);
    }

    public function test_it_ignores_updates_that_do_not_change_the_status(): void
    {
        $subscription = CorporationSubscription::factory()->create([
            'status' => SubscriptionStatus::Active,
            'billing_day' => 5,
        ]);

        $subscription->update(['billing_day' => 10]);

        $this->assertSame(0, SubscriptionStatusHistory::query()->where('subscription_id', $subscription->id)->count());
    }

    public function test_it_records_the_reason_of_the_transition(): void
    {
        $subscription = CorporationSubscription::factory()->create([
            'status' => SubscriptionStatus::Active,
        ]);

        $subscription->statusChangeReason = 'manual_review';
        $subscription->update(['status' => SubscriptionStatus::Suspended]);

        $this->assertSame(
            'manual_review',
            SubscriptionStatusHistory::query()->where('subscription_id', $subscription->id)->value('reason'),
        );
    }

    public function test_venue_subscriptions_suspended_with_the_corporation_are_recorded(): void
    {
        Notification::fake();

        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::PastDue,
            'grace_period_days' => 3,
            'trial_ends_at' => now()->subDays(30),
        ]);

        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        $venueSubscription = VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $subscription->id,
            'status' => SubscriptionStatus::Active,
        ]);

        VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'status' => InvoiceStatus::Overdue,
            'is_finalized' => false,
            'due_date' => now()->subDays(30),
        ]);

        (new SuspendOverdueSubscriptionsJob)->handle();

        // O update em massa das venues não disparava eventos e deixava a
        // suspensão sem histórico.
        $this->assertSame(
            SubscriptionStatus::Suspended->value,
            $venueSubscription->fresh()->status->value,
        );

        $this->assertDatabaseHas('subscription_status_history', [
            'subscription_id' => $venueSubscription->id,
            'to_status' => SubscriptionStatus::Suspended->value,
            'reason' => 'corporation_subscription_suspended',
        ]);

        $this->assertDatabaseHas('subscription_status_history', [
            'subscription_id' => $subscription->id,
            'to_status' => SubscriptionStatus::Suspended->value,
        ]);
    }
}
