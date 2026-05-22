<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\PlanCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanCatalog>
 */
class PlanCatalogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 10),
            'monthly_price' => fake()->randomFloat(2, 49, 999),
            'active' => true,
        ];
    }
}
