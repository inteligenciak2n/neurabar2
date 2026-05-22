<?php

namespace Database\Factories;

use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueSettings>
 */
class VenueSettingsFactory extends Factory
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
            'cover_charge' => 10.00,
            'service_fee_percent' => 10.00,
            'table_count' => 30,
        ];
    }
}
