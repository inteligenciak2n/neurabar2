<?php

namespace Database\Factories;

use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'name' => fake()->words(2, true),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
