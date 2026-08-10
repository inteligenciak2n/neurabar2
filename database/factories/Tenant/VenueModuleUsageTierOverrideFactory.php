<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\VenueModuleUsageTierOverride;
use App\Models\Tenant\VenuePlanAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VenueModuleUsageTierOverride> */
class VenueModuleUsageTierOverrideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'venue_plan_assignment_id' => VenuePlanAssignment::factory(),
            'module_code' => 'kds',
            'min_quantity' => 0,
            'max_quantity' => null,
            'included_quantity' => 0,
            'price_per_unit' => 0,
            'flat_price' => null,
            'overage_price_per_unit' => 0,
            'overage_flat_fee' => null,
            'currency' => 'BRL',
        ];
    }
}
