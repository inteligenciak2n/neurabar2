<?php

namespace Database\Factories\Payment;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentItem>
 */
class PaymentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'method' => fake()->randomElement(['cash', 'credit', 'debit', 'pix']),
            'amount' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
