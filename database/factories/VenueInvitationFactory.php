<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\VenueInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VenueInvitation>
 */
class VenueInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::Attendant,
            'token' => Str::random(64),
            'expires_at' => now()->addHours(72),
            'accepted_at' => null,
        ];
    }
}
