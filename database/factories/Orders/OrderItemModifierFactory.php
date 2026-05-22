<?php

namespace Database\Factories\Orders;

use App\Models\Menu\ModifierOption;
use App\Models\Orders\OrderItem;
use App\Models\Orders\OrderItemModifier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemModifier>
 */
class OrderItemModifierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'modifier_option_id' => ModifierOption::factory(),
            'extra_price_snapshot' => fake()->randomFloat(2, 0, 20),
        ];
    }
}
