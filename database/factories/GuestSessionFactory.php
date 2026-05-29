<?php

namespace Database\Factories;

use App\Models\GuestSession;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<GuestSession>
 */
class GuestSessionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'guest_token' => Str::uuid(),
            'pin' => Hash::make('1234'),
            'geolocation_verified' => false,
            'expires_at' => now()->addDay(),
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subHour()]);
    }
}
