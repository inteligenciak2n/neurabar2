<?php

namespace App\Actions\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Services\Billing\BillingStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

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

        DB::transaction(function () use ($subscription, $corporation): void {
            $endedAt = $subscription->trial_ends_at;

            if ($endedAt === null || $endedAt <= now()) {
                $endedAt = now()->endOfMonth();
            }

            $subscription->statusChangeReason = 'canceled_by_customer';
            $subscription->update([
                'status' => SubscriptionStatus::Canceled,
                'ended_at' => $endedAt,
            ]);

            foreach ($corporation->venues()->with('subscription')->get() as $venue) {
                $venueSubscription = $venue->subscription;

                if ($venueSubscription) {
                    $venueSubscription->statusChangeReason = 'corporation_subscription_canceled';
                    $venueSubscription->update([
                        'status' => SubscriptionStatus::Canceled,
                        'ended_at' => $endedAt,
                    ]);
                }

                BillingStatusService::flushBlockedCache($venue);
            }
        });

        $this->cancelGatewaySubscriptions($corporation, $subscription);
    }

    private function cancelGatewaySubscriptions(Corporation $corporation, CorporationSubscription $subscription): void
    {
        if ($subscription->isBilledByGateway()) {
            $this->cancelGatewaySubscriptionSafely($subscription->gateway_subscription_id);
        }

        foreach ($corporation->venues()->with('subscription')->get() as $venue) {
            $venueSubscription = $venue->subscription;

            if ($venueSubscription?->isBilledByGateway()) {
                $this->cancelGatewaySubscriptionSafely($venueSubscription->gateway_subscription_id);
            }
        }
    }

    private function cancelGatewaySubscriptionSafely(string $gatewaySubscriptionId): void
    {
        try {
            $this->gateway->cancelSubscription($gatewaySubscriptionId);
        } catch (Throwable $e) {
            Log::error('Failed to cancel gateway subscription.', [
                'gateway_subscription_id' => $gatewaySubscriptionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
