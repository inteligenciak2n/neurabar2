<?php

namespace Database\Factories;

use App\Models\Tenant\Corporation;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'corporation_id' => Corporation::factory(),
            'name' => fake()->company(),
            'tax_id' => fake()->cnpj(false),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'timezone' => 'America/Sao_Paulo',
            'active' => true,
        ];
    }

    public function withSlug(string $slug): static
    {
        return $this->state(['call_waiter_slug' => $slug]);
    }
}
