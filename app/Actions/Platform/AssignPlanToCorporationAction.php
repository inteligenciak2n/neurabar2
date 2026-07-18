<?php

namespace App\Actions\Platform;

use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PlanCatalog;

class AssignPlanToCorporationAction
{
    public function execute(Corporation $corporation, PlanCatalog $plan, array $data): void
    {
        $subscription = $corporation->subscription;

        if ($subscription) {
            $subscription->update([
                'plan_catalog_id' => $plan->id,
            ]);

            foreach ($corporation->venues as $venue) {
                $venue->subscription?->update([
                    'plan_catalog_id' => $plan->id,
                    'base_value' => $data['subscription_value'] ?? $plan->monthly_price,
                    'total_value' => ($data['subscription_value'] ?? $plan->monthly_price)
                        + (float) $venue->subscription->modules_value
                        + (float) $venue->subscription->metered_value
                        + (float) $venue->subscription->dedicated_surcharge,
                ]);
            }
        } else {
            CorporationSubscription::create([
                'corporation_id' => $corporation->id,
                'plan_catalog_id' => $plan->id,
                'billing_mode' => $data['billing_mode'] ?? 'per_venue',
                'status' => $data['status'] ?? 'trial',
                'billing_day' => $data['billing_day'] ?? config('billing.default_billing_day', 1),
                'grace_period_days' => $data['grace_period_days'] ?? config('billing.grace_period_days', 3),
                'started_at' => $data['started_at'] ?? now(),
                'trial_ends_at' => $data['trial_ends_at'] ?? now()->addDays(config('billing.trial_days', 14)),
                'currency' => $data['currency'] ?? config('billing.currency', 'BRL'),
            ]);
        }
    }
}
