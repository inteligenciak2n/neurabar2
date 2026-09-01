<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Corporation;
use App\Models\Tenant\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'corporation_id' => Corporation::factory(),
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('###########'),
        ];
    }
}
