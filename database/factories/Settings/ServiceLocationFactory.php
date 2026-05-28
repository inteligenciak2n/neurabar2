<?php

namespace Database\Factories\Settings;

use App\Enums\ServiceLocationType;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceLocation>
 */
class ServiceLocationFactory extends Factory
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
            'name' => 'Mesa '.fake()->numberBetween(1, 50),
            'type' => ServiceLocationType::Table,
            'active' => true,
        ];
    }
}
