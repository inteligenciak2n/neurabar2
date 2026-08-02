<?php

namespace App\Actions\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Services\Billing\BillingStatusService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelSubscriptionAction
{
    public function __construct(private readonly PaymentGatewayContract $gateway) {}

    public function execute(Corporation $corporation): void
    {
        $subscription = $corporation->subscription;

        if (! $subscription) {
            throw new InvalidArgumentException('No active subscription found.');
        }

        if ($subscription->status === SubscriptionStatus::Canceled) {
            throw new InvalidArgumentException('Subscription is already canceled.');
        }

        $this->cancelGatewaySubscriptions($corporation, $subscription);

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

    private function cancelGatewaySubscriptions(Corporation $corporation, CorporationSubscription $subscription): void
    {
        if ($subscription->isBilledByGateway()) {
            $this->gateway->cancelSubscription($subscription->gateway_subscription_id);
        }

        foreach ($corporation->venues as $venue) {
            $venueSubscription = $venue->subscription;

            if ($venueSubscription?->isBilledByGateway()) {
                $this->gateway->cancelSubscription($venueSubscription->gateway_subscription_id);
            }
        }
    }
}
