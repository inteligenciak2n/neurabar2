<?php

namespace App\Jobs\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\VenueSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireTrialsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        CorporationSubscription::query()
            ->where('status', SubscriptionStatus::Trial->value)
            ->where('trial_ends_at', '<=', now())
            ->update(['status' => SubscriptionStatus::PastDue->value]);

        VenueSubscription::query()
            ->where('status', SubscriptionStatus::Trial->value)
            ->where('trial_ends_at', '<=', now())
            ->update(['status' => SubscriptionStatus::PastDue->value]);
    }
}
