<?php

namespace Database\Factories\Settings;

use App\Models\Settings\TabSeries;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TabSeries>
 */
class TabSeriesFactory extends Factory
{
    protected $model = TabSeries::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'prefix' => '',
            'notes' => null,
        ];
    }
}
