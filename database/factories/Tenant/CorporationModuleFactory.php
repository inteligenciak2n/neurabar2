<?php

namespace Database\Factories\Tenant;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorporationModule>
 */
class CorporationModuleFactory extends Factory
{
    protected $model = CorporationModule::class;

    public function definition(): array
    {
        return [
            'corporation_id' => Corporation::factory(),
            'module_code' => ModuleCode::Menu->value,
            'status' => ModuleStatus::Active,
            'custom_monthly_price' => null,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }
}
