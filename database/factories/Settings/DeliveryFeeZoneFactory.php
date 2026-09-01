<?php

namespace Database\Factories\Settings;

use App\Models\Settings\DeliveryFeeZone;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryFeeZone>
 */
class DeliveryFeeZoneFactory extends Factory
{
    protected $model = DeliveryFeeZone::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'label' => fake()->word(),
            'zip_code_start' => 1000000,
            'zip_code_end' => 1999999,
            'fee' => fake()->randomFloat(2, 5, 25),
            'active' => true,
            'sort_order' => 0,
        ];
    }
}
