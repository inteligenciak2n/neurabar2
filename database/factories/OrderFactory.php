<?php

namespace Database\Factories;

use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'order_number' => fake()->numberBetween(1, 9999),
            'status' => 'open',
        ];
    }
}
