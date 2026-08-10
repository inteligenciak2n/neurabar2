<?php

namespace App\Services\Billing;

use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\PlanModuleUsageTier;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModuleUsageTierOverride;
use App\Models\Tenant\VenuePlanAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UsagePricingResolver
{
    /**
     * @return array{assignment: VenuePlanAssignment|null, tiers: Collection<int, ModuleUsageTier|PlanModuleUsageTier|VenueModuleUsageTierOverride>}
     */
    public function resolve(Venue $venue, string $moduleCode, string $period): array
    {
        $assignment = $this->resolveAssignment($venue, $period);

        if ($assignment) {
            $overrides = VenueModuleUsageTierOverride::query()
                ->where('venue_plan_assignment_id', $assignment->id)
                ->where('module_code', $moduleCode)
                ->orderBy('min_quantity')
                ->get();

            if ($overrides->isNotEmpty()) {
                return ['assignment' => $assignment, 'tiers' => $overrides];
            }

            $tiers = PlanModuleUsageTier::query()
                ->where('plan_catalog_version_id', $assignment->plan_catalog_version_id)
                ->where('module_code', $moduleCode)
                ->orderBy('min_quantity')
                ->get();

            if ($tiers->isNotEmpty()) {
                return ['assignment' => $assignment, 'tiers' => $tiers];
            }
        }

        return [
            'assignment' => null,
            'tiers' => ModuleUsageTier::query()
                ->where('module_code', $moduleCode)
                ->orderBy('min_quantity')
                ->get(),
        ];
    }

    public function resolveAssignment(Venue $venue, string $period): ?VenuePlanAssignment
    {
        $periodStart = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return VenuePlanAssignment::query()
            ->with('planCatalogVersion')
            ->where('venue_id', $venue->id)
            ->whereDate('starts_on', '<=', $periodEnd)
            ->where(function ($query) use ($periodStart): void {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $periodStart);
            })
            ->latest('starts_on')
            ->first();
    }
}
