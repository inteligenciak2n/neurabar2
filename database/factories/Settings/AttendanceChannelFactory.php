<?php

namespace Database\Factories\Settings;

use App\Enums\AttendanceChannelColor;
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
            'color' => fake()->randomElement(AttendanceChannelColor::values()),
            'description' => fake()->optional()->sentence(),
            'is_trackable' => true,
            'requires_customer_identifier' => false,
            'requires_tab' => false,
            'uses_service_location' => true,
            'active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
