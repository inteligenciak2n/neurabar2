<?php

namespace App\Actions\Platform;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationSubscription;
use App\Services\Billing\SubscriptionCalculator;
use Illuminate\Support\Facades\DB;

class UpdateCorporationSubscriptionAction
{
    public function __construct(private readonly SubscriptionCalculator $calculator) {}

    public function execute(CorporationSubscription $subscription, array $data): void
    {
        DB::transaction(function () use ($subscription, $data): void {
            $billingMode = BillingMode::tryFrom($data['billing_mode'] ?? '') ?? $subscription->billing_mode;
            $status = SubscriptionStatus::tryFrom($data['status'] ?? '') ?? $subscription->status;

            $subscription->update([
                'billing_mode' => $billingMode,
                'status' => $status,
                'billing_day' => $data['billing_day'] ?? $subscription->billing_day,
                'grace_period_days' => $data['grace_period_days'] ?? $subscription->grace_period_days,
                'started_at' => $data['started_at'] ?? $subscription->started_at,
                'trial_ends_at' => $data['trial_ends_at'] ?? $subscription->trial_ends_at,
                'ended_at' => $data['ended_at'] ?? $subscription->ended_at,
            ]);

            foreach ($subscription->corporation->venues as $venue) {
                $venueSubscription = $venue->subscription;

                if (! $venueSubscription) {
                    continue;
                }

                $calculated = $this->calculator->calculateVenue($venue, now()->format('Y-m'));

                $venueSubscription->update([
                    'status' => $status,
                    'started_at' => $subscription->started_at,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'ended_at' => $subscription->ended_at,
                    'modules_value' => $calculated['modules'] ?? $venueSubscription->modules_value,
                    'metered_value' => $calculated['metered'] ?? $venueSubscription->metered_value,
                    'total_value' => $calculated['total'] ?? $venueSubscription->total_value,
                ]);
            }

            $this->calculator->calculateCorporation($subscription->corporation, now()->format('Y-m'));
        });
    }
}
