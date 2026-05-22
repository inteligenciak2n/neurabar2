<?php

namespace Database\Factories\Menu;

use App\Models\Menu\ModifierGroup;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModifierGroup>
 */
class ModifierGroupFactory extends Factory
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
            'name' => fake()->words(2, true),
            'required' => false,
            'multiple_selection' => false,
        ];
    }
}
