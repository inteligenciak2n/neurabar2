<?php

namespace App\Services\Billing;

use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenuePlanAssignment;
use App\Models\Tenant\VenueUsageRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanCostRecommendationService
{
    public function __construct(private readonly UsageTierCalculator $tierCalculator) {}

    /**
     * @return Collection<int, array{
     *     plan_id: string,
     *     version_id: string,
     *     name: string,
     *     version: int,
     *     minimum_monthly_price: int,
     *     projected_usage_price: int,
     *     projected_total: int,
     *     savings_vs_current: int,
     *     infrastructure_type: string,
     *     is_current: bool,
     *     is_available: bool,
     *     is_recommended: bool
     * }>
     */
    public function recommend(Venue $venue, string $usagePeriod, Carbon $effectiveOn): Collection
    {
        $currentAssignment = VenuePlanAssignment::query()
            ->with([
                'planCatalogVersion.planCatalog:id,name,sort_order',
                'planCatalogVersion.usageTiers' => fn ($query) => $query->orderBy('module_code')->orderBy('min_quantity'),
                'usageTierOverrides' => fn ($query) => $query->orderBy('module_code')->orderBy('min_quantity'),
            ])
            ->where('venue_id', $venue->id)
            ->whereDate('starts_on', '<=', $effectiveOn->copy()->endOfMonth())
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $effectiveOn))
            ->latest('starts_on')
            ->first();

        $availableVersions = PlanCatalogVersion::query()
            ->where('status', 'published')
            ->whereDate('effective_from', '<=', $effectiveOn)
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $effectiveOn))
            ->whereHas('planCatalog', fn ($query) => $query->where('active', true))
            ->with([
                'planCatalog:id,name,sort_order',
                'usageTiers' => fn ($query) => $query->orderBy('module_code')->orderBy('min_quantity'),
            ])
            ->orderByDesc('effective_from')
            ->orderByDesc('version')
            ->get()
            ->unique('plan_catalog_id')
            ->values();

        $versions = collect($availableVersions->all());

        if ($currentAssignment && $versions->doesntContain('id', $currentAssignment->plan_catalog_version_id)) {
            $versions->push($currentAssignment->planCatalogVersion);
        }

        $records = VenueUsageRecord::query()
            ->where('venue_id', $venue->id)
            ->where('period', $usagePeriod)
            ->get(['module_code', 'quantity']);
        $legacyTiers = ModuleUsageTier::query()
            ->whereIn('module_code', $records->pluck('module_code'))
            ->orderBy('module_code')
            ->orderBy('min_quantity')
            ->get()
            ->groupBy('module_code');
        $overrides = $currentAssignment?->usageTierOverrides->groupBy('module_code') ?? collect();

        $recommendations = $versions->map(function (PlanCatalogVersion $version) use ($availableVersions, $currentAssignment, $legacyTiers, $overrides, $records): array {
            $isCurrent = $currentAssignment?->plan_catalog_version_id === $version->id;
            $versionTiers = $version->usageTiers->groupBy('module_code');
            $usagePrice = $records->sum(function (VenueUsageRecord $record) use ($isCurrent, $legacyTiers, $overrides, $versionTiers): int {
                $tiers = $isCurrent && $overrides->has($record->module_code)
                    ? $overrides->get($record->module_code)
                    : $versionTiers->get($record->module_code, $legacyTiers->get($record->module_code, collect()));

                return $this->tierCalculator->calculate($tiers, (int) $record->quantity)['total_price'];
            });

            return [
                'plan_id' => $version->plan_catalog_id,
                'version_id' => $version->id,
                'name' => $version->planCatalog->name,
                'version' => $version->version,
                'minimum_monthly_price' => $version->minimum_monthly_price,
                'projected_usage_price' => $usagePrice,
                'projected_total' => $version->minimum_monthly_price + $usagePrice,
                'savings_vs_current' => 0,
                'infrastructure_type' => $version->infrastructure_type,
                'is_current' => $isCurrent,
                'is_available' => $availableVersions->contains('id', $version->id),
                'is_recommended' => false,
            ];
        })->sortBy([
            ['projected_total', 'asc'],
            ['minimum_monthly_price', 'asc'],
            ['name', 'asc'],
        ])->values();

        $currentCost = $recommendations->firstWhere('is_current', true)['projected_total'] ?? null;
        $recommendedVersionId = $recommendations->first()['version_id'] ?? null;

        return $recommendations->map(function (array $recommendation) use ($currentCost, $recommendedVersionId): array {
            $recommendation['savings_vs_current'] = $currentCost === null ? 0 : $currentCost - $recommendation['projected_total'];
            $recommendation['is_recommended'] = $recommendation['version_id'] === $recommendedVersionId;

            return $recommendation;
        });
    }
}
