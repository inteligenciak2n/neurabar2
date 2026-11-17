<?php

namespace Database\Factories\Orders;

use App\Models\Orders\VenueCustomer;
use App\Models\Orders\VenueCustomerAddress;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueCustomerAddress>
 */
class VenueCustomerAddressFactory extends Factory
{
    protected $model = VenueCustomerAddress::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'venue_customer_id' => VenueCustomer::factory(),
            'zip_code' => fake()->numerify('########'),
            'street' => fake()->streetName(),
            'number' => (string) fake()->buildingNumber(),
            'complement' => fake()->optional()->secondaryAddress(),
            'neighborhood' => fake()->word(),
            'city' => fake()->city(),
            'state' => fake()->lexify('??'),
            'reference_point' => fake()->optional()->sentence(3),
            'delivery_phone' => null,
            'is_default' => false,
        ];
    }
}
