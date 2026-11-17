<?php

namespace Database\Factories\Orders;

use App\Models\Orders\VenueCustomer;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueCustomer>
 */
class VenueCustomerFactory extends Factory
{
    protected $model = VenueCustomer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'name' => fake()->name(),
            'phone' => '43'.fake()->unique()->numerify('9########'),
            'address' => fake()->optional()->streetAddress(),
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
