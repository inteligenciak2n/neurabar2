<?php

namespace Database\Factories\Orders;

use App\Enums\PaymentMethod;
use App\Models\Orders\DeliveryOrder;
use App\Models\Orders\DeliveryOrderPaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOrderPaymentMethod>
 */
class DeliveryOrderPaymentMethodFactory extends Factory
{
    protected $model = DeliveryOrderPaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_order_id' => DeliveryOrder::factory(),
            'method' => PaymentMethod::Cash,
            'amount' => fake()->randomFloat(2, 10, 100),
        ];
    }
}
