<?php

namespace Database\Factories\Tenant;

use App\Enums\ModuleCode;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\PlanModuleUsageTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanModuleUsageTier>
 */
class PlanModuleUsageTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_catalog_version_id' => PlanCatalogVersion::factory(),
            'module_code' => ModuleCode::Kds->value,
            'min_quantity' => 0,
            'max_quantity' => null,
            'included_quantity' => 100,
            'price_per_unit' => 0,
            'flat_price' => null,
            'overage_price_per_unit' => 500,
            'overage_flat_fee' => null,
            'currency' => 'BRL',
        ];
    }
}
