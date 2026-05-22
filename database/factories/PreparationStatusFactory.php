<?php

namespace Database\Factories;

use App\Models\Settings\PreparationStatus;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PreparationStatus>
 */
class PreparationStatusFactory extends Factory
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
            'name' => fake()->word(),
            'color' => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(1, 10),
            'show_to_customer' => false,
        ];
    }
}
