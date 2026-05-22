<?php

namespace Database\Factories;

use App\Models\Orders\Attendance;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
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
            'channel' => 'table',
            'status' => 'open',
            'party_size' => fake()->numberBetween(1, 8),
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => 'open', 'closed_at' => null]);
    }

    public function closed(): static
    {
        return $this->state(['status' => 'closed', 'closed_at' => now()]);
    }
}
