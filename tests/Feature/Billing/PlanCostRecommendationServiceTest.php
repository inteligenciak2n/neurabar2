<?php

namespace Tests\Feature\Billing;

use App\Enums\ModuleCode;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\PlanModuleUsageTier;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenuePlanAssignment;
use App\Models\Tenant\VenueUsageRecord;
use App\Services\Billing\PlanCostRecommendationService;
use Illuminate\Support\Carbon;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PlanCostRecommendationServiceTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_recommendation_uses_total_projected_cost_instead_of_minimum_commitment(): void
    {
        $venue = Venue::factory()->create();
        $currentPlan = PlanCatalog::factory()->create(['name' => 'Volume']);
        $currentVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $currentPlan->id,
            'effective_from' => '2026-01-01',
            'minimum_monthly_price' => 50000,
        ]);
        PlanModuleUsageTier::factory()->create([
            'plan_catalog_version_id' => $currentVersion->id,
            'module_code' => ModuleCode::Kds->value,
            'included_quantity' => 1000,
            'overage_price_per_unit' => 0,
        ]);
        VenuePlanAssignment::factory()->create([
            'venue_id' => $venue->id,
            'plan_catalog_id' => $currentPlan->id,
            'plan_catalog_version_id' => $currentVersion->id,
            'starts_on' => '2026-01-01',
        ]);

        $lowerCommitmentPlan = PlanCatalog::factory()->create(['name' => 'Entry']);
        $lowerCommitmentVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $lowerCommitmentPlan->id,
            'effective_from' => '2026-01-01',
            'minimum_monthly_price' => 30000,
        ]);
        PlanModuleUsageTier::factory()->create([
            'plan_catalog_version_id' => $lowerCommitmentVersion->id,
            'module_code' => ModuleCode::Kds->value,
            'included_quantity' => 0,
            'overage_price_per_unit' => 3000,
        ]);
        VenueUsageRecord::create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'period' => '2026-07',
            'quantity' => 1000,
        ]);

        $recommendations = app(PlanCostRecommendationService::class)
            ->recommend($venue, '2026-07', Carbon::parse('2026-08-01'));

        $this->assertSame($currentVersion->id, $recommendations->first()['version_id']);
        $this->assertTrue($recommendations->first()['is_recommended']);
        $this->assertSame(50000, $recommendations->first()['projected_total']);
        $this->assertSame(60000, $recommendations->last()['projected_total']);
        $this->assertSame(-10000, $recommendations->last()['savings_vs_current']);
    }

    public function test_new_version_of_the_current_plan_is_available_without_replacing_the_current_contract(): void
    {
        $venue = Venue::factory()->create();
        $plan = PlanCatalog::factory()->create();
        $currentVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $plan->id,
            'version' => 1,
            'effective_from' => '2026-01-01',
            'effective_until' => '2026-07-31',
            'minimum_monthly_price' => 50000,
        ]);
        $newVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $plan->id,
            'version' => 2,
            'effective_from' => '2026-08-01',
            'minimum_monthly_price' => 40000,
        ]);
        VenuePlanAssignment::factory()->create([
            'venue_id' => $venue->id,
            'plan_catalog_id' => $plan->id,
            'plan_catalog_version_id' => $currentVersion->id,
            'starts_on' => '2026-01-01',
        ]);

        $recommendations = app(PlanCostRecommendationService::class)
            ->recommend($venue, '2026-07', Carbon::parse('2026-08-01'));

        $this->assertTrue($recommendations->firstWhere('version_id', $currentVersion->id)['is_current']);
        $this->assertFalse($recommendations->firstWhere('version_id', $currentVersion->id)['is_available']);
        $this->assertFalse($recommendations->firstWhere('version_id', $newVersion->id)['is_current']);
        $this->assertTrue($recommendations->firstWhere('version_id', $newVersion->id)['is_available']);
        $this->assertTrue($recommendations->firstWhere('version_id', $newVersion->id)['is_recommended']);
    }
}
