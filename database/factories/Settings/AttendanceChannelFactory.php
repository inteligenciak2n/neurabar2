<?php

namespace Database\Factories\Settings;

use App\Models\Settings\AttendanceChannel;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $name = fake()->randomElement(['Balcão', 'Mesa', 'Delivery', 'Retirada']);

        return [
            'venue_id' => Venue::factory(),
            'name' => $name,
            'value' => Str::slug($name, '_'),
            'active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
