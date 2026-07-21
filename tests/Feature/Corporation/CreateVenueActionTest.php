<?php

namespace Tests\Feature\Corporation;

use App\Actions\Corporation\CreateVenueAction;
use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class CreateVenueActionTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_creates_venue_with_subscription_and_propagates_modules(): void
    {
        $plan = PlanCatalog::factory()->create(['monthly_price' => 199.00]);
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        CorporationModule::factory()->create([
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $action = app()->make(CreateVenueAction::class);
        $venue = $action->execute($corporation, [
            'name' => 'Nova Venue',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venue->id,
            'plan_catalog_id' => $plan->id,
            'base_value' => 199.00,
        ]);

        $this->assertDatabaseHas('venue_modules', [
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active->value,
        ]);

        $this->assertDatabaseHas('venue_modules', [
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Menu->value,
            'status' => ModuleStatus::Active->value,
        ]);
    }

    public function test_it_recalculates_subscription_with_propagated_modules(): void
    {
        $plan = PlanCatalog::factory()->create(['monthly_price' => 100.00]);
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        ModuleCatalog::updateOrCreate(
            ['code' => ModuleCode::Kds->value],
            [
                'name' => ModuleCode::Kds->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 25.00,
                'dependencies' => [],
                'active' => true,
            ]
        );

        CorporationModule::factory()->create([
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $action = app()->make(CreateVenueAction::class);
        $venue = $action->execute($corporation, [
            'name' => 'Nova Venue',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venue->id,
            'base_value' => 100.00,
            'modules_value' => 25.00,
            'total_value' => 125.00,
        ]);
    }

    public function test_it_throws_when_corporation_has_no_subscription(): void
    {
        $corporation = Corporation::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A corporation não possui uma assinatura ativa');

        $action = app()->make(CreateVenueAction::class);
        $action->execute($corporation, [
            'name' => 'Nova Venue',
            'timezone' => 'America/Sao_Paulo',
        ]);
    }
}
