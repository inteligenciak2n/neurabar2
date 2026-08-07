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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ExpireTrialsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function handle(): void
    {
        CorporationSubscription::query()
            ->where('status', SubscriptionStatus::Trial->value)
            ->where('trial_ends_at', '<=', now())
            ->with('corporation.venues', 'corporation.owner')
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $this->expireCorporationTrial($subscription);
                }
            });

        VenueSubscription::query()
            ->where('status', SubscriptionStatus::Trial->value)
            ->where('trial_ends_at', '<=', now())
            ->with('venue')
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $venueSubscription) {
                    $this->expireVenueTrial($venueSubscription);
                }
            });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('billing.expire_trials.failed', [
            'message' => $exception->getMessage(),
        ]);
    }

    private function expireCorporationTrial(CorporationSubscription $subscription): void
    {
        // A recurring subscription already exists at the gateway: the trial
        // simply rolls into the paid cycle. Marking it past_due would flag a
        // paying customer as delinquent and start the suspension countdown.
        $convertedToPaid = $subscription->isBilledByGateway();

        $subscription->statusChangeReason = $convertedToPaid ? 'trial_converted_to_paid' : 'trial_expired';
        $subscription->update([
            'status' => $convertedToPaid
                ? SubscriptionStatus::Active->value
                : SubscriptionStatus::PastDue->value,
        ]);

        $corporation = $subscription->corporation;

        if (! $corporation) {
            return;
        }

        foreach ($corporation->venues as $venue) {
            BillingStatusService::flushBlockedCache($venue);
        }

        $owner = $corporation->owner;

        if ($owner && ! $convertedToPaid) {
            Notification::send($owner, new TrialExpired($corporation));
        }
    }

    private function expireVenueTrial(VenueSubscription $venueSubscription): void
    {
        $convertedToPaid = $venueSubscription->isBilledByGateway();

        $venueSubscription->statusChangeReason = $convertedToPaid ? 'trial_converted_to_paid' : 'trial_expired';
        $venueSubscription->update([
            'status' => $convertedToPaid
                ? SubscriptionStatus::Active->value
                : SubscriptionStatus::PastDue->value,
        ]);

        if ($venueSubscription->venue) {
            BillingStatusService::flushBlockedCache($venueSubscription->venue);
        }
    }
}
