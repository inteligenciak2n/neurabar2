<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\GatewayCustomer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GatewayCustomer>
 */
class GatewayCustomerFactory extends Factory
{
    protected $model = GatewayCustomer::class;

    public function definition(): array
    {
        return [
            'owner_type' => User::class,
            'owner_id' => User::factory(),
            'gateway' => 'fake',
            'customer_id' => 'fake_cus_'.fake()->uuid(),
        ];
    }
}
