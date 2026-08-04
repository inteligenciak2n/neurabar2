<?php

namespace Tests\Feature\Corporation;

use App\Actions\Corporation\CreateVenueAction;
use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\Venue\CreateVenueDefaultsJob;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use Illuminate\Support\Facades\Queue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class CreateVenueActionTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_creates_venue_with_subscription_and_propagates_modules(): void
    {
        $plan = PlanCatalog::factory()->create(['monthly_price' => 19900]);
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
            'base_value' => 19900,
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

    public function test_it_applies_dedicated_surcharge_only_for_dedicated_corporations(): void
    {
        $plan = PlanCatalog::factory()->create([
            'monthly_price' => 19900,
            'dedicated_surcharge' => 5000,
        ]);

        $dedicated = Corporation::factory()->create(['is_dedicated' => true]);
        CorporationSubscription::factory()->create([
            'corporation_id' => $dedicated->id,
            'plan_catalog_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $shared = Corporation::factory()->create(['is_dedicated' => false]);
        CorporationSubscription::factory()->create([
            'corporation_id' => $shared->id,
            'plan_catalog_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $action = app()->make(CreateVenueAction::class);

        $dedicatedVenue = $action->execute($dedicated, [
            'name' => 'Venue Dedicada',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $sharedVenue = $action->execute($shared, [
            'name' => 'Venue Compartilhada',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $dedicatedVenue->id,
            'dedicated_surcharge' => 5000,
        ]);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $sharedVenue->id,
            'dedicated_surcharge' => 0,
        ]);
    }

    public function test_it_recalculates_subscription_with_propagated_modules(): void
    {
        $plan = PlanCatalog::factory()->create(['monthly_price' => 10000]);
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
                'base_monthly_price' => 2500,
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
            'base_value' => 10000,
            'modules_value' => 2500,
            'total_value' => 12500,
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

    public function test_it_queues_the_default_provisioning_instead_of_running_it_inline(): void
    {
        Queue::fake();

        $plan = PlanCatalog::factory()->create(['monthly_price' => 19900]);
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $action = app()->make(CreateVenueAction::class);
        $venue = $action->execute($corporation, [
            'name' => 'Nova Venue',
            'timezone' => 'America/Sao_Paulo',
        ]);

        Queue::assertPushed(
            CreateVenueDefaultsJob::class,
            fn (CreateVenueDefaultsJob $job): bool => $job->venueId() === $venue->id
        );
    }
}
