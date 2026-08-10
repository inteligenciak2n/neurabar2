<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanCatalogVersion>
 */
class PlanCatalogVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_catalog_id' => PlanCatalog::factory(),
            'version' => 1,
            'status' => 'published',
            'effective_from' => now()->startOfMonth(),
            'minimum_monthly_price' => fake()->numberBetween(0, 99900),
            'infrastructure_type' => 'shared',
            'currency' => 'BRL',
        ];
    }
}
