<?php

namespace Tests\Feature\Module;

use App\Actions\Corporation\ActivateVenueModuleAction;
use App\Actions\Corporation\DeactivateVenueModuleAction;
use App\Actions\Platform\DisableCorporateModuleAction;
use App\Actions\Platform\EnableCorporateModuleAction;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Tests\TestCase;

class ModuleActivationActionsTest extends TestCase
{
    public function test_enable_corporate_module_action_activates_module(): void
    {
        $venue = Venue::factory()->create();
        $corporation = $venue->corporation;

        $action = new EnableCorporateModuleAction;
        $module = $action->execute($corporation, ModuleCode::Kds->value, 49.90);

        $this->assertInstanceOf(CorporationModule::class, $module);
        $this->assertEquals(ModuleStatus::Active, $module->status);
        $this->assertEquals(49.90, $module->custom_monthly_price);
        $this->assertDatabaseHas('corporation_modules', [
            'id' => $module->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active->value,
        ]);
    }

    public function test_disable_corporate_module_action_deactivates_module(): void
    {
        $venue = Venue::factory()->create();
        $corporation = $venue->corporation;

        CorporationModule::factory()->create([
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $action = new DisableCorporateModuleAction;
        $action->execute($corporation, ModuleCode::Kds->value);

        $this->assertDatabaseHas('corporation_modules', [
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Inactive->value,
        ]);
    }

    public function test_activate_venue_module_action_activates_module(): void
    {
        $venue = Venue::factory()->create();

        $action = new ActivateVenueModuleAction;
        $module = $action->execute($venue, ModuleCode::Kds->value, 2);

        $this->assertInstanceOf(VenueModule::class, $module);
        $this->assertEquals(ModuleStatus::Active, $module->status);
        $this->assertEquals(2, $module->quantity);
    }

    public function test_deactivate_venue_module_action_deactivates_module(): void
    {
        $venue = Venue::factory()->create();

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $action = new DeactivateVenueModuleAction;
        $action->execute($venue, ModuleCode::Kds->value);

        $this->assertDatabaseHas('venue_modules', [
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Inactive->value,
        ]);
    }
}
