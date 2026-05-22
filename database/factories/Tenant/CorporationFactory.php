<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Corporation;
use App\Models\Tenant\PlanCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Corporation>
 */
class CorporationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'tax_id' => fake()->numerify('##.###.###/####-##'),
            'email' => fake()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'plan_catalog_id' => PlanCatalog::factory(),
            'plan_name' => 'Pro',
            'subscription_value' => 199.00,
            'active' => true,
        ];
    }
}
