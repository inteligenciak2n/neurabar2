<?php

namespace Database\Factories\Tenant;

use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Models\Tenant\ModuleCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModuleCatalog>
 */
class ModuleCatalogFactory extends Factory
{
    protected $model = ModuleCatalog::class;

    public function definition(): array
    {
        return [
            'code' => ModuleCode::Menu->value,
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'category' => 'basic',
            'billing_type' => ModuleBillingType::Fixed,
            'base_monthly_price' => 0,
            'unit_of_measure' => null,
            'dependencies' => [],
            'required_roles' => null,
            'icon' => null,
            'sort_order' => 0,
            'active' => true,
        ];
    }
}
