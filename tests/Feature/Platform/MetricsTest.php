<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
use App\Models\Tenant\Corporation;
use App\Services\Platform\MetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mrr_is_sum_of_active_corporations_with_valid_plan(): void
    {
        Corporation::factory()->create(['active' => true, 'subscription_value' => 199.00, 'plan_end_date' => today()->addMonth()]);
        Corporation::factory()->create(['active' => true, 'subscription_value' => 299.00, 'plan_end_date' => today()->addMonth()]);
        Corporation::factory()->create(['active' => false, 'subscription_value' => 100.00, 'plan_end_date' => today()->addMonth()]);
        Corporation::factory()->create(['active' => true, 'subscription_value' => 99.00, 'plan_end_date' => today()->subDay()]);

        $service = new MetricsService;
        $mrr = $service->calculateMRR();

        $this->assertEquals(498.00, $mrr);
    }

    public function test_operational_summary_returns_correct_counts(): void
    {
        Corporation::factory()->count(3)->create(['active' => true, 'plan_end_date' => today()->addMonth()]);
        Corporation::factory()->count(2)->create(['active' => false]);

        $service = new MetricsService;
        $summary = $service->operationalSummary();

        $this->assertEquals(5, $summary['total_corporations']);
        $this->assertEquals(3, $summary['active_corporations']);
        $this->assertEquals(2, $summary['inactive_corporations']);
    }

    public function test_backoffice_dashboard_is_accessible(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::ReadOnly);

        $this->get(route('platform.dashboard'))->assertOk();
    }
}
