<?php

namespace Database\Factories\Menu;

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'servings' => 1,
            'price' => fake()->randomFloat(2, 5, 150),
            'active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
