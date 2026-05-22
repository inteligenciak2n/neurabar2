<?php

namespace Database\Factories\Menu;

use App\Models\Menu\ModifierGroup;
use App\Models\Menu\ModifierOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModifierOption>
 */
class ModifierOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'modifier_group_id' => ModifierGroup::factory(),
            'name' => fake()->word(),
            'extra_price' => fake()->randomFloat(2, 0, 20),
            'active' => true,
        ];
    }
}
