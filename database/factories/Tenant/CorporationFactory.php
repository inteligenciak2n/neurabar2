<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Corporation;
use App\Models\User;
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
            'owner_id' => User::factory(),
            'name' => fake()->company(),
            'tax_id' => fake()->numerify('##.###.###/####-##'),
            'email' => fake()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'active' => true,
            'self_connection' => 'operation_default_1',
            'is_dedicated' => false,
        ];
    }
}
