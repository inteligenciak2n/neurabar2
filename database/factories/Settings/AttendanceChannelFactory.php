<?php

namespace Database\Factories\Settings;

use App\Models\Settings\AttendanceChannel;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceChannel>
 */
class AttendanceChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'name' => fake()->randomElement(['Balcão', 'Mesa', 'Delivery', 'Retirada']),
            'is_trackable' => true,
            'requires_customer_identifier' => false,
            'active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
