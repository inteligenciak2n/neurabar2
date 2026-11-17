<?php

namespace Database\Factories\Orders;

use App\Models\Orders\Attendance;
use App\Models\Orders\AttendanceDeliveryAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceDeliveryAddress>
 */
class AttendanceDeliveryAddressFactory extends Factory
{
    protected $model = AttendanceDeliveryAddress::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'delivery_fee' => fake()->randomFloat(2, 0, 15),
            'zip_code' => fake()->numerify('########'),
            'street' => fake()->streetName(),
            'number' => (string) fake()->buildingNumber(),
            'complement' => fake()->optional()->secondaryAddress(),
            'neighborhood' => fake()->word(),
            'city' => fake()->city(),
            'state' => fake()->lexify('??'),
            'reference_point' => fake()->optional()->sentence(3),
            'delivery_phone' => null,
        ];
    }
}
