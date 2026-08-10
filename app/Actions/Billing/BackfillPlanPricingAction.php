<?php

namespace App\Actions\Billing;

use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\VenuePlanAssignment;
use App\Models\Tenant\VenueSubscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BackfillPlanPricingAction
{
    /** @return array{versions_created: int, tiers_created: int, assignments_created: int} */
    public function execute(): array
    {
        return DB::connection('saas')->transaction(function (): array {
            $result = [
                'versions_created' => 0,
                'tiers_created' => 0,
                'assignments_created' => 0,
            ];
            $legacyTiers = ModuleUsageTier::query()
                ->orderBy('module_code')
                ->orderBy('min_quantity')
                ->get();
            $plans = PlanCatalog::query()->orderBy('sort_order')->lockForUpdate()->get();

            foreach ($plans as $plan) {
                $version = $plan->versions()->where('status', 'published')
                    ->whereDate('effective_from', '<=', today())
                    ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()))
                    ->latest('effective_from')
                    ->first();

                if (! $plan->versions()->exists()) {
                    $version = $this->createInitialVersion($plan, $legacyTiers->first()?->currency ?? 'BRL');
                    $result['versions_created']++;

                    foreach ($legacyTiers as $legacyTier) {
                        $version->usageTiers()->create($legacyTier->only([
                            'module_code', 'min_quantity', 'max_quantity', 'included_quantity',
                            'price_per_unit', 'flat_price', 'overage_price_per_unit',
                            'overage_flat_fee', 'currency',
                        ]));
                        $result['tiers_created']++;
                    }
                }

                if (! $version) {
                    continue;
                }

                $subscriptions = VenueSubscription::query()
                    ->where('plan_catalog_id', $plan->id)
                    ->whereDoesntHave('venue.planAssignments')
                    ->get();

                foreach ($subscriptions as $subscription) {
                    $subscriptionStart = $subscription->started_at?->copy()->startOfMonth() ?? $version->effective_from->copy();
                    $startsOn = $subscriptionStart->max($version->effective_from)->toDateString();

                    VenuePlanAssignment::create([
                        'venue_id' => $subscription->venue_id,
                        'plan_catalog_id' => $plan->id,
                        'plan_catalog_version_id' => $version->id,
                        'starts_on' => $startsOn,
                        'source' => 'legacy_backfill',
                    ]);
                    $result['assignments_created']++;
                }
            }

            return $result;
        });
    }

    private function createInitialVersion(PlanCatalog $plan, string $currency): PlanCatalogVersion
    {
        $earliestSubscriptionStart = VenueSubscription::query()
            ->where('plan_catalog_id', $plan->id)
            ->min('started_at');
        $effectiveFrom = $earliestSubscriptionStart
            ? Carbon::parse($earliestSubscriptionStart)->startOfMonth()
            : ($plan->created_at?->copy()->startOfMonth() ?? now()->startOfMonth());

        return $plan->versions()->create([
            'version' => 1,
            'status' => 'published',
            'effective_from' => $effectiveFrom,
            'minimum_monthly_price' => (int) $plan->monthly_price,
            'infrastructure_type' => in_array($plan->plan_type, ['shared', 'dedicated'], true) ? $plan->plan_type : 'shared',
            'currency' => strtoupper($currency),
        ]);
    }
}
