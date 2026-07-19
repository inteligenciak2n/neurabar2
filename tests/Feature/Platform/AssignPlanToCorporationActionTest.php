<?php

namespace Tests\Feature\Platform;

use App\Actions\Platform\AssignPlanToCorporationAction;
use App\Enums\BillingMode;
use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class AssignPlanToCorporationActionTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_creates_subscriptions_for_corporation_and_venues(): void
    {
        $corporation = Corporation::factory()->create();
        Venue::factory()->count(2)->create(['corporation_id' => $corporation->id]);
        $plan = PlanCatalog::factory()->create(['monthly_price' => 199.90]);

        $action = app()->make(AssignPlanToCorporationAction::class);
        $action->execute($corporation, $plan, [
            'subscription_value' => 199.90,
            'billing_mode' => BillingMode::PerVenue->value,
            'billing_day' => 10,
            'grace_period_days' => 3,
            'started_at' => now()->toDateString(),
            'trial_ends_at' => now()->addDays(14)->toDateString(),
        ]);

        $this->assertDatabaseHas('corporation_subscriptions', [
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
            'billing_mode' => BillingMode::PerVenue->value,
            'status' => SubscriptionStatus::Trial->value,
        ]);

        foreach ($corporation->venues as $venue) {
            $this->assertDatabaseHas('venue_subscriptions', [
                'venue_id' => $venue->id,
                'base_value' => 199.90,
                'total_value' => 199.90,
            ]);
        }
    }

    public function test_it_recalculates_existing_modules_when_assigning_plan(): void
    {
        $corporation = Corporation::factory()->create();
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        $plan = PlanCatalog::factory()->create();

        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
        ]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporation->subscription->id,
            'base_value' => 100.00,
            'total_value' => 100.00,
            'status' => SubscriptionStatus::Active,
        ]);

        ModuleCatalog::updateOrCreate(
            ['code' => ModuleCode::Kds->value],
            [
                'name' => ModuleCode::Kds->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 50.00,
                'dependencies' => [],
                'active' => true,
            ]
        );
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

        $action = app()->make(AssignPlanToCorporationAction::class);
        $action->execute($corporation, $plan, [
            'subscription_value' => 100.00,
            'billing_mode' => BillingMode::PerVenue->value,
            'billing_day' => 10,
            'grace_period_days' => 3,
            'started_at' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('venue_subscriptions', [
            'venue_id' => $venue->id,
            'base_value' => 100.00,
            'modules_value' => 50.00,
            'total_value' => 150.00,
        ]);
    }
}
