<?php

namespace App\Jobs\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\VenueSubscription;
use App\Notifications\Billing\TrialExpired;
use App\Services\Billing\BillingStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class ExpireTrialsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $corporationSubscriptions = CorporationSubscription::query()
            ->where('status', SubscriptionStatus::Trial->value)
            ->where('trial_ends_at', '<=', now())
            ->get();

        foreach ($corporationSubscriptions as $subscription) {
            $subscription->update(['status' => SubscriptionStatus::PastDue->value]);

            $corporation = $subscription->corporation;

            if ($corporation) {
                foreach ($corporation->venues as $venue) {
                    BillingStatusService::flushBlockedCache($venue);
                }

                $owner = $corporation->owner;

                if ($owner) {
                    Notification::send($owner, new TrialExpired($corporation));
                }
            }
        }

        $venueSubscriptions = VenueSubscription::query()
            ->where('status', SubscriptionStatus::Trial->value)
            ->where('trial_ends_at', '<=', now())
            ->get();

        foreach ($venueSubscriptions as $venueSubscription) {
            $venueSubscription->update(['status' => SubscriptionStatus::PastDue->value]);

            if ($venueSubscription->venue) {
                BillingStatusService::flushBlockedCache($venueSubscription->venue);
            }
        }
    }
}
