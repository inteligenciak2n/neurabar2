<?php

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Services\Billing\BillingStatusService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelSubscriptionAction
{
    public function execute(Corporation $corporation): void
    {
        $subscription = $corporation->subscription;

        if (! $subscription) {
            throw new InvalidArgumentException('No active subscription found.');
        }

        if ($subscription->status === SubscriptionStatus::Canceled) {
            throw new InvalidArgumentException('Subscription is already canceled.');
        }

        DB::transaction(function () use ($subscription, $corporation): void {
            $endedAt = $subscription->trial_ends_at;

            if ($endedAt === null || $endedAt <= now()) {
                $endedAt = now()->endOfMonth();
            }

            $subscription->update([
                'status' => SubscriptionStatus::Canceled,
                'ended_at' => $endedAt,
            ]);

            foreach ($corporation->venues()->cursor() as $venue) {
                $venueSubscription = $venue->subscription;

                if ($venueSubscription) {
                    $venueSubscription->update([
                        'status' => SubscriptionStatus::Canceled,
                        'ended_at' => $endedAt,
                    ]);
                }

                BillingStatusService::flushBlockedCache($venue);
            }
        });
    }
}
