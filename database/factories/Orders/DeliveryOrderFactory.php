<?php

namespace Database\Factories\Orders;

use App\Enums\FulfillmentType;
use App\Models\Orders\Attendance;
use App\Models\Orders\DeliveryOrder;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOrder>
 */
class DeliveryOrderFactory extends Factory
{
    protected $model = DeliveryOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'attendance_id' => Attendance::factory(),
            'fulfillment_type' => FulfillmentType::Pickup,
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('###########'),
            'delivery_fee' => 0,
        ];
    }

    public function delivery(): static
    {
        return $this->state([
            'fulfillment_type' => FulfillmentType::Delivery,
            'address_street' => fake()->streetName(),
            'address_number' => fake()->buildingNumber(),
            'address_neighborhood' => fake()->word(),
            'address_city' => fake()->city(),
            'address_state' => fake()->stateAbbr(),
            'address_zip_code' => fake()->numerify('########'),
            'delivery_fee' => fake()->randomFloat(2, 5, 25),
        ]);
    }
}
