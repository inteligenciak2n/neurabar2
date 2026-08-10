<?php

namespace App\Services\Billing;

use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\PlanModuleUsageTier;
use App\Support\Money;
use Illuminate\Support\Collection;

class UsageTierCalculator
{
    /**
     * @param  Collection<int, ModuleUsageTier|PlanModuleUsageTier>  $tiers
     * @return array{included_quantity: int, overage_quantity: int, base_price: int, overage_price: int, total_price: int, last_tier: ModuleUsageTier|PlanModuleUsageTier|null}
     */
    public function calculate(Collection $tiers, int $quantity): array
    {
        $quantity = max(0, $quantity);
        $basePrice = 0;
        $overagePrice = 0;
        $includedQuantity = 0;
        $overageQuantity = 0;
        $lastTier = null;
        $consumedUnits = 0;

        foreach ($tiers as $tier) {
            $upperBound = $tier->max_quantity !== null
                ? min($quantity, (int) $tier->max_quantity)
                : $quantity;
            $unitsInTier = max(0, $upperBound - $consumedUnits);
            $consumedUnits = max($consumedUnits, $upperBound);

            if ($unitsInTier === 0) {
                continue;
            }

            $lastTier = $tier;
            $tierIncluded = min($unitsInTier, max(0, (int) ($tier->included_quantity ?? 0)));
            $tierOverage = $unitsInTier - $tierIncluded;
            $includedQuantity += $tierIncluded;
            $overageQuantity += $tierOverage;
            $basePrice += $tier->flat_price !== null
                ? (int) $tier->flat_price
                : Money::fromMicros((int) $tier->price_per_unit * $tierIncluded);

            if ($tierOverage > 0) {
                $overagePrice += (int) ($tier->overage_flat_fee ?? 0);
                $overagePrice += Money::fromMicros($tierOverage * (int) $tier->overage_price_per_unit);
            }
        }

        return [
            'included_quantity' => $includedQuantity,
            'overage_quantity' => $overageQuantity,
            'base_price' => $basePrice,
            'overage_price' => $overagePrice,
            'total_price' => $basePrice + $overagePrice,
            'last_tier' => $lastTier,
        ];
    }
}
