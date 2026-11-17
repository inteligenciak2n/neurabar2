<?php

namespace Database\Factories\Settings;

use App\Enums\KitchenStationCode;
use App\Models\Settings\KitchenStation;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KitchenStation>
 */
class KitchenStationFactory extends Factory
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
            'name' => fake()->randomElement(['Kitchen', 'Bar', 'Grill', 'Fryer']),
            'code' => null,
            'sort_order' => fake()->numberBetween(1, 10),
            'active' => true,
        ];
    }

    public function waiterCaller(): static
    {
        return $this->state([
            'name' => KitchenStationCode::WaiterCaller->defaultName(),
            'code' => KitchenStationCode::WaiterCaller,
            'sort_order' => 0,
            'active' => true,
        ]);
    }
}
