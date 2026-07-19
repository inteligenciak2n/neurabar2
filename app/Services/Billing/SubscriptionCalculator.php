<?php

namespace App\Services\Billing;

use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueUsageRecord;

class SubscriptionCalculator
{
    /**
     * @return array<string, float>
     */
    public function calculateVenue(Venue $venue, string $period): array
    {
        $subscription = $venue->subscription;

        if (! $subscription) {
            return $this->emptyResult();
        }

        if ($this->hasFinalizedInvoice($venue, $period)) {
            return $this->emptyResult();
        }

        $base = (float) $subscription->base_value;
        $modulesValue = $this->calculateModules($venue);
        $metered = $this->calculateMetered($venue, $period);
        $dedicatedSurcharge = (float) ($subscription->dedicated_surcharge ?? 0);

        $total = $base + $modulesValue + $metered + $dedicatedSurcharge;

        $subscription->update([
            'modules_value' => $modulesValue,
            'metered_value' => $metered,
            'total_value' => $total,
        ]);

        return [
            'base' => $base,
            'modules' => $modulesValue,
            'metered' => $metered,
            'dedicated_surcharge' => $dedicatedSurcharge,
            'total' => $total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function calculateCorporation(Corporation $corporation, string $period): array
    {
        $venueTotals = [];
        $grandTotal = 0.0;

        foreach ($corporation->venues as $venue) {
            $venueTotals[$venue->id] = $this->calculateVenue($venue, $period);
            $grandTotal += $venueTotals[$venue->id]['total'];
        }

        return [
            'venues' => $venueTotals,
            'total' => $grandTotal,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function emptyResult(): array
    {
        return [
            'base' => 0.0,
            'modules' => 0.0,
            'metered' => 0.0,
            'dedicated_surcharge' => 0.0,
            'total' => 0.0,
        ];
    }

    private function hasFinalizedInvoice(Venue $venue, string $period): bool
    {
        $invoice = VenueInvoice::query()
            ->where('venue_id', $venue->id)
            ->where('period', $period)
            ->where('is_finalized', true)
            ->first();

        return $invoice !== null;
    }

    private function calculateModules(Venue $venue): float
    {
        $total = 0.0;

        $venueModules = $venue->modules()
            ->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
            ->where(function ($query): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            })
            ->get();

        foreach ($venueModules as $venueModule) {
            $unitPrice = $this->resolveModuleUnitPrice($venue, $venueModule->module_code);

            if ($unitPrice === null) {
                continue;
            }

            $total += $unitPrice * max(1, (int) $venueModule->quantity);
        }

        return $total;
    }

    private function resolveModuleUnitPrice(Venue $venue, string $moduleCode): ?float
    {
        $corporationModule = $venue->corporation?->activeModules()
            ->where('module_code', $moduleCode)
            ->first();

        if (! $corporationModule) {
            return null;
        }

        if ($corporationModule->custom_monthly_price !== null) {
            return (float) $corporationModule->custom_monthly_price;
        }

        return (float) ($corporationModule->catalog?->base_monthly_price ?? 0);
    }

    private function calculateMetered(Venue $venue, string $period): float
    {
        $total = 0.0;

        $records = VenueUsageRecord::query()
            ->where('venue_id', $venue->id)
            ->where('period', $period)
            ->get();

        foreach ($records as $record) {
            $total += $this->calculateRecord($record);
        }

        return $total;
    }

    private function calculateRecord(VenueUsageRecord $record): float
    {
        $tier = $this->resolveTier($record);

        if (! $tier) {
            return 0.0;
        }

        $included = (int) ($tier->included_quantity ?? 0);
        $quantity = max(0, (int) $record->quantity);
        $overageQuantity = max(0, $quantity - $included);

        $basePrice = $tier->flat_price !== null
            ? (float) $tier->flat_price
            : ((float) $tier->price_per_unit * $quantity);

        $overagePrice = 0.0;

        if ($overageQuantity > 0) {
            $overagePrice += (float) ($tier->overage_flat_fee ?? 0);
            $overagePrice += $overageQuantity * (float) $tier->overage_price_per_unit;
        }

        $record->update([
            'tier_id' => $tier->id,
            'included_quantity' => min($quantity, $included),
            'overage_quantity' => $overageQuantity,
            'base_calculated_price' => $basePrice,
            'overage_calculated_price' => $overagePrice,
            'total_calculated_price' => $basePrice + $overagePrice,
        ]);

        return $basePrice + $overagePrice;
    }

    private function resolveTier(VenueUsageRecord $record): ?ModuleUsageTier
    {
        $quantity = (int) $record->quantity;

        $query = ModuleUsageTier::query()
            ->where('module_code', $record->module_code)
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity): void {
                $query->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderBy('min_quantity', 'desc')
            ->first();

        return $query;
    }
}
