<?php

namespace Database\Factories;

use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Models\Tenant\ModuleCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModuleCatalog>
 */
class ModuleCatalogFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(ModuleCode::values()),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'category' => 'premium',
            'billing_type' => ModuleBillingType::Fixed,
            'base_monthly_price' => fake()->numberBetween(0, 99900),
            'unit_of_measure' => null,
            'dependencies' => [],
            'required_roles' => ['owner'],
            'icon' => null,
            'sort_order' => fake()->numberBetween(1, 100),
            'active' => true,
        ];
    }
}
