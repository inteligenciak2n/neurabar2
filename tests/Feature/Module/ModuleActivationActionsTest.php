<?php

namespace Tests\Feature\Module;

use App\Actions\Corporation\ActivateVenueModuleAction;
use App\Actions\Platform\DisableCorporateModuleAction;
use App\Actions\Platform\EnableCorporateModuleAction;
use App\Actions\Subscription\UnsubscribeModuleAction;
use App\Enums\BillingMode;
use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ModuleActivationActionsTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_enable_corporate_module_action_activates_module(): void
    {
        $venue = Venue::factory()->create();
        $corporation = $venue->corporation;
        $this->createCatalogModule(ModuleCode::Kds, 4990);

        $action = app()->make(EnableCorporateModuleAction::class);
        $module = $action->execute($corporation, ModuleCode::Kds->value, 4990);

        $this->assertInstanceOf(CorporationModule::class, $module);
        $this->assertEquals(ModuleStatus::Active, $module->status);
        $this->assertSame(4990, $module->custom_monthly_price);
        $this->assertDatabaseHas('corporation_modules', [
            'id' => $module->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active->value,
        ]);
    }

    public function test_enable_corporate_module_throws_when_catalog_is_inactive(): void
    {
        $corporation = Corporation::factory()->create();
        ModuleCatalog::updateOrCreate(
            ['code' => ModuleCode::Kds->value],
            [
                'name' => ModuleCode::Kds->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 4990,
                'dependencies' => [],
                'active' => false,
            ]
        );

        $this->expectException(\InvalidArgumentException::class);

        $action = app()->make(EnableCorporateModuleAction::class);
        $action->execute($corporation, ModuleCode::Kds->value);
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

        $action = app()->make(DisableCorporateModuleAction::class);
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
        $corporation = $venue->corporation;

        CorporationModule::factory()->create([
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $action = app()->make(ActivateVenueModuleAction::class);
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

        $action = app()->make(UnsubscribeModuleAction::class);
        $action->execute($venue, ModuleCode::Kds->value);

        $this->assertDatabaseHas('venue_modules', [
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Inactive->value,
        ]);
    }

    public function test_activate_venue_module_recalculates_subscription_value(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 10000);
        $corporation = $venue->corporation;
        $this->createCatalogModule(ModuleCode::Kds, 3000);
        CorporationModule::factory()->create([
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $action = app()->make(ActivateVenueModuleAction::class);
        $action->execute($venue, ModuleCode::Kds->value, 2);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venue->id,
            'base_value' => 10000,
            'modules_value' => 6000,
            'total_value' => 16000,
        ]);
    }

    public function test_deactivate_venue_module_recalculates_subscription_value(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 10000);
        $corporation = $venue->corporation;
        $this->createCatalogModule(ModuleCode::Kds, 3000);
        CorporationModule::factory()->create([
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);
        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
            'quantity' => 1,
        ]);

        $action = app()->make(UnsubscribeModuleAction::class);
        $action->execute($venue, ModuleCode::Kds->value);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venue->id,
            'modules_value' => 0,
            'total_value' => 10000,
        ]);
    }

    public function test_enable_corporate_module_recalculates_all_venue_subscriptions(): void
    {
        $corporation = Corporation::factory()->create();
        $venueA = $this->createVenueForCorporation($corporation, baseValue: 10000);
        $venueB = $this->createVenueForCorporation($corporation, baseValue: 10000);
        $this->createCatalogModule(ModuleCode::Kds, 2500);

        $action = app()->make(EnableCorporateModuleAction::class);
        $action->execute($corporation, ModuleCode::Kds->value);

        VenueModule::factory()->create([
            'venue_id' => $venueA->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);
        VenueModule::factory()->create([
            'venue_id' => $venueB->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $action = app()->make(EnableCorporateModuleAction::class);
        $action->execute($corporation, ModuleCode::Kds->value);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venueA->id,
            'modules_value' => 2500,
            'total_value' => 12500,
        ]);
        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venueB->id,
            'modules_value' => 2500,
            'total_value' => 12500,
        ]);
    }

    public function test_disable_corporate_module_recalculates_all_venue_subscriptions(): void
    {
        $corporation = Corporation::factory()->create();
        $venue = $this->createVenueForCorporation($corporation, baseValue: 10000);
        $this->createCatalogModule(ModuleCode::Kds, 2500);
        CorporationModule::factory()->create([
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);
        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $action = app()->make(DisableCorporateModuleAction::class);
        $action->execute($corporation, ModuleCode::Kds->value);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venue->id,
            'modules_value' => 0,
            'total_value' => 10000,
        ]);
    }

    private function createVenueWithSubscription(int $baseValue): Venue
    {
        $venue = Venue::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
        ]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $venue->corporation->subscription->id,
            'base_value' => $baseValue,
            'total_value' => $baseValue,
            'status' => SubscriptionStatus::Active,
        ]);

        return $venue->fresh();
    }

    private function createVenueForCorporation(Corporation $corporation, int $baseValue): Venue
    {
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => CorporationSubscription::factory()->create([
                'corporation_id' => $corporation->id,
                'billing_mode' => BillingMode::PerVenue,
                'status' => SubscriptionStatus::Active,
            ])->id,
            'base_value' => $baseValue,
            'total_value' => $baseValue,
            'status' => SubscriptionStatus::Active,
        ]);

        return $venue->fresh();
    }

    private function createCatalogModule(ModuleCode $code, int $price): void
    {
        ModuleCatalog::updateOrCreate(
            ['code' => $code->value],
            [
                'name' => $code->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => $price,
                'dependencies' => [],
                'active' => true,
                'sort_order' => 1,
            ]
        );
    }
}
