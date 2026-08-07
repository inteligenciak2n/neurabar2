<?php

namespace Tests\Feature\Billing\Jobs;

use App\Enums\ModuleCode;
use App\Jobs\Billing\RecordModuleUsageJob;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RecordModuleUsageJobTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_creates_usage_record_for_venue_and_period(): void
    {
        $venue = Venue::factory()->create();

        (new RecordModuleUsageJob($venue->id, ModuleCode::Kds->value, 5))->handle();

        $this->assertDatabaseHas('venue_usage_records', [
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'period' => now()->format('Y-m'),
            'quantity' => 5,
        ]);
    }

    public function test_it_increments_existing_usage_record(): void
    {
        $venue = Venue::factory()->create();

        (new RecordModuleUsageJob($venue->id, ModuleCode::Kds->value, 3))->handle();
        (new RecordModuleUsageJob($venue->id, ModuleCode::Kds->value, 2))->handle();

        $this->assertDatabaseHas('venue_usage_records', [
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'period' => now()->format('Y-m'),
            'quantity' => 5,
        ]);
    }
}
