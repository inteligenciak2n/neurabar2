<?php

namespace Database\Factories\Payment;

use App\Models\Orders\Attendance;
use App\Models\Payment\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $items = fake()->randomFloat(2, 50, 500);
        $cover = 10.00;
        $fee = round($items * 0.1, 2);
        $total = $items + $cover + $fee;

        return [
            'attendance_id' => Attendance::factory(),
            'items_total' => $items,
            'cover_charge_total' => $cover,
            'service_fee_total' => $fee,
            'grand_total' => $total,
            'party_size' => fake()->numberBetween(1, 8),
            'created_by' => User::factory(),
        ];
    }

    public function scopeToday(): static
    {
        return $this->state(['created_at' => now()]);
    }
}
