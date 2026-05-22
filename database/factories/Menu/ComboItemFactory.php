<?php

namespace Database\Factories\Menu;

use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComboItem>
 */
class ComboItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'combo_id' => Combo::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 3),
        ];
    }
}
