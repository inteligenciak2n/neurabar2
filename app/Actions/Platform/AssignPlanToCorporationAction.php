<?php

namespace App\Actions\Platform;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\VenueSubscription;
use App\Services\Billing\SubscriptionCalculator;
use App\Services\VenueModuleCache;
use Illuminate\Support\Facades\DB;

class AssignPlanToCorporationAction
{
    public function __construct(private readonly SubscriptionCalculator $calculator) {}

    public function execute(Corporation $corporation, PlanCatalog $plan, array $data): void
    {
        DB::transaction(function () use ($corporation, $plan, $data): void {
            $subscription = $corporation->subscription;

            $baseValue = (float) ($data['subscription_value'] ?? $plan->monthly_price);
            $billingMode = BillingMode::tryFrom($data['billing_mode'] ?? '') ?? BillingMode::PerVenue;
            $status = SubscriptionStatus::tryFrom($data['status'] ?? '') ?? SubscriptionStatus::Trial;

            if ($subscription) {
                $subscription->update([
                    'plan_catalog_id' => $plan->id,
                    'billing_mode' => $billingMode->value,
                    'status' => $status->value,
                    'billing_day' => $data['billing_day'] ?? config('billing.default_billing_day', 1),
                    'grace_period_days' => $data['grace_period_days'] ?? config('billing.grace_period_days', 3),
                    'started_at' => $data['started_at'] ?? now(),
                    'trial_ends_at' => $data['trial_ends_at'] ?? null,
                    'ended_at' => null,
                ]);
            } else {
                $subscription = CorporationSubscription::create([
                    'corporation_id' => $corporation->id,
                    'plan_catalog_id' => $plan->id,
                    'billing_mode' => $billingMode->value,
                    'status' => $status->value,
                    'billing_day' => $data['billing_day'] ?? config('billing.default_billing_day', 1),
                    'grace_period_days' => $data['grace_period_days'] ?? config('billing.grace_period_days', 3),
                    'started_at' => $data['started_at'] ?? now(),
                    'trial_ends_at' => $data['trial_ends_at'] ?? now()->addDays(config('billing.trial_days', 14)),
                    'currency' => $data['currency'] ?? config('billing.currency', 'BRL'),
                ]);
            }

            foreach ($corporation->venues as $venue) {
                $venueSubscription = $venue->subscription;

                if ($venueSubscription) {
                    $venueSubscription->update([
                        'corporation_subscription_id' => $subscription->id,
                        'plan_catalog_id' => $plan->id,
                        'status' => $status->value,
                        'base_value' => $baseValue,
                        'total_value' => $baseValue
                            + (float) $venueSubscription->modules_value
                            + (float) $venueSubscription->metered_value
                            + (float) $venueSubscription->dedicated_surcharge,
                        'started_at' => $data['started_at'] ?? now(),
                        'trial_ends_at' => $subscription->trial_ends_at,
                        'ended_at' => null,
                    ]);
                } else {
                    VenueSubscription::create([
                        'venue_id' => $venue->id,
                        'corporation_subscription_id' => $subscription->id,
                        'plan_catalog_id' => $plan->id,
                        'status' => $status->value,
                        'base_value' => $baseValue,
                        'modules_value' => 0,
                        'metered_value' => 0,
                        'dedicated_surcharge' => 0,
                        'total_value' => $baseValue,
                        'started_at' => $data['started_at'] ?? now(),
                        'trial_ends_at' => $subscription->trial_ends_at,
                    ]);
                }

                VenueModuleCache::forget($venue);
            }

            $this->calculator->refreshCorporationSnapshot($corporation, now()->format('Y-m'));
        });
    }
}
