<?php

namespace Tests\Feature\Billing;

use App\Models\AuditLog;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\PlanModuleUsageTier;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class UpdateCatalogPricesCommandTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_updates_module_price_and_publishes_a_new_plan_version(): void
    {
        $plan = PlanCatalog::factory()->create([
            'code' => 'basic',
            'monthly_price' => 9900,
        ]);
        $currentVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $plan->id,
            'version' => 1,
            'effective_from' => '2026-01-01',
            'minimum_monthly_price' => 9900,
        ]);
        $tier = PlanModuleUsageTier::factory()->create([
            'plan_catalog_version_id' => $currentVersion->id,
            'overage_price_per_unit' => 500,
        ]);
        $module = ModuleCatalog::factory()->create([
            'code' => 'kds',
            'base_monthly_price' => 4990,
        ]);

        $this->artisan('billing:update-prices', [
            '--plan' => ['basic=149.90'],
            '--module' => ['kds=59.90'],
            '--effective-from' => '2026-09-01',
            '--force' => true,
        ])->assertSuccessful();

        $newVersion = PlanCatalogVersion::query()
            ->where('plan_catalog_id', $plan->id)
            ->where('version', 2)
            ->firstOrFail();

        $this->assertSame(14990, $plan->fresh()->monthly_price);
        $this->assertSame(5990, $module->fresh()->base_monthly_price);
        $this->assertSame('published', $newVersion->status);
        $this->assertSame(14990, $newVersion->minimum_monthly_price);
        $this->assertSame('2026-09-01', $newVersion->effective_from->toDateString());
        $this->assertSame('2026-08-31', $currentVersion->fresh()->effective_until->toDateString());
        $this->assertDatabaseHas('plan_module_usage_tiers', [
            'plan_catalog_version_id' => $newVersion->id,
            'module_code' => $tier->module_code,
            'overage_price_per_unit' => 500,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'plan.price.updated',
            'auditable_id' => $plan->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'module_catalog.updated',
            'auditable_id' => $module->id,
        ]);
        $this->assertSame(3, AuditLog::query()->count());
    }

    public function test_it_rolls_back_all_updates_when_a_catalog_code_is_invalid(): void
    {
        $module = ModuleCatalog::factory()->create([
            'code' => 'kds',
            'base_monthly_price' => 4990,
        ]);

        $this->artisan('billing:update-prices', [
            '--module' => ['kds=59.90', 'missing=10.00'],
            '--force' => true,
        ])->assertFailed();

        $this->assertSame(4990, $module->fresh()->base_monthly_price);
    }

    public function test_it_rejects_an_invalid_price_before_writing(): void
    {
        $this->artisan('billing:update-prices', [
            '--module' => ['kds=59,90'],
            '--force' => true,
        ])->assertFailed();
    }
}
