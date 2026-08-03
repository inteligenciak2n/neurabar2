<?php

namespace Tests\Feature\Corporation;

use App\Actions\Corporation\ProvisionPlanModulesAction;
use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueSubscription;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ProvisionPlanModulesActionTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_activates_plan_modules_on_corporation_and_venue(): void
    {
        $plan = PlanCatalog::factory()->withModules([
            ModuleCode::Menu->value,
            ModuleCode::Kds->value,
        ])->create();

        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
        ]);
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporation->subscription->id,
            'plan_catalog_id' => $plan->id,
            'base_value' => $plan->monthly_price,
            'total_value' => $plan->monthly_price,
        ]);

        $this->seedCatalogs();

        $action = app()->make(ProvisionPlanModulesAction::class);
        $action->execute($corporation, $venue, $plan);

        $this->assertDatabaseHas('corporation_modules', [
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Menu->value,
            'status' => ModuleStatus::Trial->value,
        ]);
        $this->assertDatabaseHas('corporation_modules', [
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Trial->value,
        ]);

        $this->assertDatabaseHas('venue_modules', [
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Menu->value,
            'status' => ModuleStatus::Trial->value,
        ]);
        $this->assertDatabaseHas('venue_modules', [
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Trial->value,
        ]);
    }

    public function test_it_recalculates_venue_subscription_with_module_values(): void
    {
        $plan = PlanCatalog::factory()->withModules([
            ModuleCode::Kds->value,
        ])->create();

        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
        ]);
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporation->subscription->id,
            'plan_catalog_id' => $plan->id,
            'base_value' => 10000,
            'total_value' => 10000,
        ]);

        ModuleCatalog::updateOrCreate(
            ['code' => ModuleCode::Kds->value],
            [
                'name' => ModuleCode::Kds->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 5000,
                'dependencies' => [],
                'active' => true,
            ]
        );

        $action = app()->make(ProvisionPlanModulesAction::class);
        $action->execute($corporation, $venue, $plan);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venue->id,
            'base_value' => 10000,
            'modules_value' => 5000,
            'total_value' => 15000,
        ]);
    }

    public function test_it_throws_when_module_is_not_active_in_catalog(): void
    {
        $plan = PlanCatalog::factory()->withModules([
            ModuleCode::Kds->value,
        ])->create();

        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
        ]);
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);

        ModuleCatalog::updateOrCreate(
            ['code' => ModuleCode::Kds->value],
            [
                'name' => ModuleCode::Kds->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 5000,
                'dependencies' => [],
                'active' => false,
            ]
        );

        $this->expectException(\InvalidArgumentException::class);

        $action = app()->make(ProvisionPlanModulesAction::class);
        $action->execute($corporation, $venue, $plan);
    }

    private function seedCatalogs(): void
    {
        foreach ([ModuleCode::Menu, ModuleCode::Kds] as $code) {
            ModuleCatalog::updateOrCreate(
                ['code' => $code->value],
                [
                    'name' => $code->label(),
                    'billing_type' => ModuleBillingType::Fixed,
                    'base_monthly_price' => 0,
                    'dependencies' => [],
                    'active' => true,
                ]
            );
        }
    }
}
