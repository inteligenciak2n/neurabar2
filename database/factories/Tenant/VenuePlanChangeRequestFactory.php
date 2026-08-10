<?php

namespace Database\Factories\Tenant;

use App\Enums\VenuePlanChangeStatus;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenuePlanChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenuePlanChangeRequest>
 */
class VenuePlanChangeRequestFactory extends Factory
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
            'pending_venue_id' => fn (array $attributes) => $attributes['venue_id'],
            'requested_plan_catalog_id' => PlanCatalog::factory(),
            'requested_plan_catalog_version_id' => PlanCatalogVersion::factory(),
            'requested_by' => User::factory(),
            'status' => VenuePlanChangeStatus::Pending,
            'effective_on' => now()->addMonthNoOverflow()->startOfMonth(),
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
