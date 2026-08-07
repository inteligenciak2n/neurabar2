<?php

namespace Database\Factories\Tenant;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueModule>
 */
class VenueModuleFactory extends Factory
{
    protected $model = VenueModule::class;

    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'module_code' => ModuleCode::Menu->value,
            'status' => ModuleStatus::Active,
            'quantity' => 1,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }
}
