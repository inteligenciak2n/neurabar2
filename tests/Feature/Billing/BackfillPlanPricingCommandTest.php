<?php

namespace Tests\Feature\Billing;

use App\Actions\Billing\BackfillPlanPricingAction;
use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueSubscription;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class BackfillPlanPricingCommandTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_backfill_creates_initial_pricing_and_is_idempotent(): void
    {
        $plan = PlanCatalog::factory()->create([
            'monthly_price' => 24900,
            'plan_type' => 'shared',
        ]);
        ModuleUsageTier::create([
            'module_code' => 'kds',
            'min_quantity' => 0,
            'max_quantity' => 1000,
            'included_quantity' => 100,
            'price_per_unit' => 0,
            'flat_price' => 0,
            'overage_price_per_unit' => 500,
            'overage_flat_fee' => 0,
            'currency' => 'BRL',
        ]);
        ModuleUsageTier::create([
            'module_code' => 'kds',
            'min_quantity' => 1001,
            'max_quantity' => null,
            'included_quantity' => 100,
            'price_per_unit' => 0,
            'flat_price' => 0,
            'overage_price_per_unit' => 300,
            'overage_flat_fee' => 0,
            'currency' => 'BRL',
        ]);
        $venue = Venue::factory()->create();
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'plan_catalog_id' => $plan->id,
            'started_at' => '2026-01-15',
        ]);

        $firstRun = app(BackfillPlanPricingAction::class)->execute();
        $secondRun = app(BackfillPlanPricingAction::class)->execute();

        $this->assertSame([
            'versions_created' => 1,
            'tiers_created' => 2,
            'assignments_created' => 1,
        ], $firstRun);
        $this->assertSame([
            'versions_created' => 0,
            'tiers_created' => 0,
            'assignments_created' => 0,
        ], $secondRun);
        $this->assertDatabaseHas('plan_catalog_versions', [
            'plan_catalog_id' => $plan->id,
            'version' => 1,
            'minimum_monthly_price' => 24900,
            'effective_from' => '2026-01-01',
        ]);
        $this->assertDatabaseCount('plan_module_usage_tiers', 2);
        $this->assertDatabaseHas('venue_plan_assignments', [
            'venue_id' => $venue->id,
            'plan_catalog_id' => $plan->id,
            'starts_on' => '2026-01-01',
            'source' => 'legacy_backfill',
        ]);

        $this->artisan('billing:backfill-plan-pricing')->assertSuccessful();
    }
}
