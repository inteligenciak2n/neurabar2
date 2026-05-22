<?php

namespace Database\Factories\Menu;

use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariation>
 */
class ProductVariationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->word(),
            'price' => fake()->randomFloat(2, 5, 150),
            'active' => true,
        ];
    }
}
