<?php

namespace App\Jobs\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\VenueSubscription;
use App\Notifications\Billing\SubscriptionSuspended;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SuspendOverdueSubscriptionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $corporationSubscriptions = CorporationSubscription::query()
            ->where('status', SubscriptionStatus::PastDue->value)
            ->whereRaw("trial_ends_at + INTERVAL '1 day' * grace_period_days <= ?", [now()])
            ->get();

        foreach ($corporationSubscriptions as $subscription) {
            $subscription->update([
                'status' => SubscriptionStatus::Suspended->value,
                'ended_at' => now(),
            ]);

            $owner = $subscription->corporation?->owner;

            if ($owner) {
                Notification::send($owner, new SubscriptionSuspended($subscription->corporation));
            }
        }

        VenueSubscription::query()
            ->where('status', SubscriptionStatus::PastDue->value)
            ->whereHas('corporationSubscription', function ($query): void {
                $query->whereRaw("trial_ends_at + INTERVAL '1 day' * grace_period_days <= ?", [now()]);
            })
            ->update([
                'status' => SubscriptionStatus::Suspended->value,
                'ended_at' => now(),
            ]);
    }
}
