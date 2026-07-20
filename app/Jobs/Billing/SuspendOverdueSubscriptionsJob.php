<?php

namespace App\Jobs\Billing;

use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use App\Notifications\Billing\SubscriptionSuspended;
use App\Services\Billing\BillingStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SuspendOverdueSubscriptionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $this->suspendExpiredTrials();
        $this->suspendOverdueUnifiedCorporations();
        $this->suspendOverdueVenues();
    }

    private function suspendExpiredTrials(): void
    {
        $corporationSubscriptions = CorporationSubscription::query()
            ->where('status', SubscriptionStatus::PastDue->value)
            ->whereRaw("trial_ends_at + INTERVAL '1 day' * grace_period_days <= ?", [now()])
            ->get();

        foreach ($corporationSubscriptions as $subscription) {
            $this->suspendCorporationSubscription($subscription);
        }
    }

    private function suspendOverdueUnifiedCorporations(): void
    {
        $threshold = now();

        $corporationIds = CorporationInvoice::query()
            ->select('corporation_id')
            ->where('status', InvoiceStatus::Overdue->value)
            ->where('is_finalized', false)
            ->whereExists(function ($query) use ($threshold): void {
                $query->select(DB::raw(1))
                    ->from('corporation_subscriptions')
                    ->whereColumn('corporation_subscriptions.id', 'corporation_invoices.corporation_subscription_id')
                    ->where('corporation_subscriptions.billing_mode', BillingMode::Unified->value)
                    ->whereRaw("corporation_invoices.due_date + INTERVAL '1 day' * corporation_subscriptions.grace_period_days <= ?", [$threshold]);
            })
            ->groupBy('corporation_id')
            ->pluck('corporation_id');

        foreach ($corporationIds as $corporationId) {
            $subscription = CorporationSubscription::query()
                ->where('corporation_id', $corporationId)
                ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value])
                ->first();

            if ($subscription) {
                $this->suspendCorporationSubscription($subscription);
            }
        }
    }

    private function suspendOverdueVenues(): void
    {
        $threshold = now();

        $venueIds = VenueInvoice::query()
            ->select('venue_id')
            ->where('status', InvoiceStatus::Overdue->value)
            ->where('is_finalized', false)
            ->whereExists(function ($query) use ($threshold): void {
                $query->select(DB::raw(1))
                    ->from('corporation_subscriptions')
                    ->join('venue_subscriptions', 'venue_subscriptions.corporation_subscription_id', '=', 'corporation_subscriptions.id')
                    ->whereColumn('venue_subscriptions.venue_id', 'venue_invoices.venue_id')
                    ->where('corporation_subscriptions.billing_mode', BillingMode::PerVenue->value)
                    ->whereRaw("venue_invoices.due_date + INTERVAL '1 day' * corporation_subscriptions.grace_period_days <= ?", [$threshold]);
            })
            ->groupBy('venue_id')
            ->pluck('venue_id');

        foreach ($venueIds as $venueId) {
            $subscription = VenueSubscription::query()
                ->where('venue_id', $venueId)
                ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value])
                ->first();

            if (! $subscription) {
                continue;
            }

            $subscription->update([
                'status' => SubscriptionStatus::Suspended->value,
                'ended_at' => now(),
            ]);

            $venue = $subscription->venue;

            if ($venue) {
                BillingStatusService::flushBlockedCache($venue);
            }
        }
    }

    private function suspendCorporationSubscription(CorporationSubscription $subscription): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::Suspended->value,
            'ended_at' => now(),
        ]);

        VenueSubscription::query()
            ->where('corporation_subscription_id', $subscription->id)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value])
            ->update([
                'status' => SubscriptionStatus::Suspended->value,
                'ended_at' => now(),
            ]);

        $corporation = $subscription->corporation;

        if ($corporation) {
            foreach ($corporation->venues as $venue) {
                BillingStatusService::flushBlockedCache($venue);
            }

            $owner = $corporation->owner;

            if ($owner) {
                Notification::send($owner, new SubscriptionSuspended($corporation));
            }
        }
    }
}
