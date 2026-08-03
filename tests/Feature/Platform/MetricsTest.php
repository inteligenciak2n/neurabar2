<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\PlanCatalog;
use App\Services\Platform\MetricsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    use RefreshAllDatabases;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_mrr_is_sum_of_active_subscriptions_with_valid_plan(): void
    {
        $plan199 = PlanCatalog::factory()->create(['monthly_price' => 19900]);
        $plan299 = PlanCatalog::factory()->create(['monthly_price' => 29900]);
        $plan100 = PlanCatalog::factory()->create(['monthly_price' => 10000]);
        $plan99 = PlanCatalog::factory()->create(['monthly_price' => 9900]);

        $this->createActiveCorporationWithSubscription($plan199);
        $this->createActiveCorporationWithSubscription($plan299);

        $inactiveCorp = Corporation::factory()->create(['active' => false]);
        $this->createSubscriptionFor($inactiveCorp, $plan100);

        $expiredCorp = Corporation::factory()->create(['active' => true]);
        $this->createSubscriptionFor($expiredCorp, $plan99, endedAt: today()->subDay());

        $service = new MetricsService;
        $mrr = $service->calculateMRR();

        $this->assertSame(49800, $mrr);
    }

    public function test_operational_summary_returns_correct_counts(): void
    {
        Cache::store('array')->flush();

        $initialTotal = Corporation::count();
        $initialActive = Corporation::where('active', true)->count();
        $initialInactive = Corporation::where('active', false)->count();

        $plan = PlanCatalog::factory()->create(['monthly_price' => 9900]);

        Corporation::factory()->count(3)->create(['active' => true])->each(function (Corporation $corp) use ($plan): void {
            $this->createSubscriptionFor($corp, $plan);
        });
        Corporation::factory()->count(2)->create(['active' => false]);

        $service = new MetricsService;
        $summary = $service->operationalSummary();

        $this->assertEquals($initialTotal + 5, $summary['total_corporations']);
        $this->assertEquals($initialActive + 3, $summary['active_corporations']);
        $this->assertEquals($initialInactive + 2, $summary['inactive_corporations']);
    }

    private function createActiveCorporationWithSubscription(PlanCatalog $plan): Corporation
    {
        $corporation = Corporation::factory()->create(['active' => true]);

        return $this->createSubscriptionFor($corporation, $plan);
    }

    private function createSubscriptionFor(Corporation $corporation, PlanCatalog $plan, ?Carbon $endedAt = null): Corporation
    {
        $corporation->subscriptions()->create([
            'plan_catalog_id' => $plan->id,
            'billing_mode' => 'per_venue',
            'status' => 'active',
            'billing_day' => 1,
            'grace_period_days' => 3,
            'started_at' => now(),
            'ended_at' => $endedAt,
            'currency' => 'BRL',
        ]);

        return $corporation;
    }

    public function test_backoffice_dashboard_is_accessible(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::ReadOnly);

        $this->get(route('platform.dashboard'))->assertOk();
    }
}
