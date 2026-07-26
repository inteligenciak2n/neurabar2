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

        if (in_array($subscription->status, [SubscriptionStatus::Canceled, SubscriptionStatus::Suspended], true)) {
            throw new InvalidArgumentException('Subscription is already canceled or suspended.');
        }

        DB::transaction(function () use ($subscription, $corporation): void {
            $endedAt = $subscription->trial_ends_at ?? $subscription->ended_at;

            if ($endedAt === null || $endedAt <= now()) {
                $endedAt = now()->endOfMonth();
            }

            $subscription->update([
                'ended_at' => $endedAt,
            ]);

            foreach ($corporation->venues as $venue) {
                $venueSubscription = $venue->subscription;

                if ($venueSubscription) {
                    $venueSubscription->update([
                        'ended_at' => $endedAt,
                    ]);
                }

                BillingStatusService::flushBlockedCache($venue);
            }
        });
    }
}
