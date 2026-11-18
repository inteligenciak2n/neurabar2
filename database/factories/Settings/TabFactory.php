<?php

namespace Database\Factories\Settings;

use App\Models\Settings\Tab;
use App\Models\Settings\TabSeries;
use App\Models\Tenant\Venue;
use App\Support\TabCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tab>
 */
class TabFactory extends Factory
{
    protected $model = Tab::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'tab_series_id' => function (array $attributes) {
                $venueId = $attributes['venue_id'] ?? Venue::factory()->create()->id;
                $prefix = TabCode::prefixFromCode((string) ($attributes['code'] ?? ''));

                return TabSeries::query()
                    ->withoutGlobalScopes()
                    ->firstOrCreate(
                        [
                            'venue_id' => $venueId,
                            'prefix' => $prefix,
                        ],
                        [
                            'notes' => null,
                        ],
                    )->id;
            },
            'code' => (string) fake()->unique()->numberBetween(1, 9999),
            'active' => true,
        ];
    }
}
