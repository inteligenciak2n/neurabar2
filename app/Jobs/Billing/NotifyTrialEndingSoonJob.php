<?php

namespace App\Jobs\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationSubscription;
use App\Notifications\Billing\TrialEndingSoon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyTrialEndingSoonJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $subscriptions = CorporationSubscription::query()
            ->where('status', SubscriptionStatus::Trial->value)
            ->where('trial_ends_at', '<=', now()->addDays(3))
            ->where('trial_ends_at', '>', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            $owner = $subscription->corporation?->owner;

            if ($owner) {
                Notification::send($owner, new TrialEndingSoon($subscription->corporation));
            }
        }
    }
}
