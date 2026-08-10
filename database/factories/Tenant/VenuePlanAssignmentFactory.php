<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenuePlanAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenuePlanAssignment>
 */
class VenuePlanAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'plan_catalog_id' => PlanCatalog::factory(),
            'plan_catalog_version_id' => PlanCatalogVersion::factory(),
            'starts_on' => now()->startOfMonth(),
            'ends_on' => null,
            'source' => 'backoffice',
        ];
    }
}
