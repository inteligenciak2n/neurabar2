<?php

namespace Database\Factories\Tenant;

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
            'tax_id' => fake()->numerify('##.###.###/####-##'),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'timezone' => 'America/Sao_Paulo',
            'active' => true,
            'call_waiter_slug' => fake()->unique()->slug(),
        ];
    }

    public function withSlug(string $slug): static
    {
        return $this->state(['call_waiter_slug' => $slug]);
    }
}
