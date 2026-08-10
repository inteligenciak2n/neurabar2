<?php

namespace Tests\Feature\Billing;

use App\Actions\Billing\UpdateCatalogPricesAction;
use App\Models\AuditLog;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\PlanModuleUsageTier;
use Illuminate\Support\Carbon;
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

    public function test_action_uses_consumption_tiers_related_by_plan_code(): void
    {
        $basic = $this->createPlanWithPublishedTier('basic');
        $pro = $this->createPlanWithPublishedTier('pro');
        ModuleCatalog::factory()->create(['code' => 'kds']);

        app(UpdateCatalogPricesAction::class)->execute(
            ['basic' => 14900, 'pro' => 24900],
            [],
            Carbon::parse('2026-09-01'),
            [
                'basic' => [[
                    'module_code' => 'kds',
                    'min_quantity' => 0,
                    'max_quantity' => null,
                    'included_quantity' => 200,
                    'price_per_unit' => 0,
                    'flat_price' => 0,
                    'overage_price_per_unit' => 700,
                    'overage_flat_fee' => 0,
                    'currency' => 'BRL',
                ]],
                'pro' => [[
                    'module_code' => 'kds',
                    'min_quantity' => 0,
                    'max_quantity' => null,
                    'included_quantity' => 500,
                    'price_per_unit' => 0,
                    'flat_price' => 0,
                    'overage_price_per_unit' => 400,
                    'overage_flat_fee' => 0,
                    'currency' => 'BRL',
                ]],
            ],
        );

        $this->assertDatabaseHas('plan_module_usage_tiers', [
            'plan_catalog_version_id' => $basic->versions()->where('version', 2)->value('id'),
            'included_quantity' => 200,
            'overage_price_per_unit' => 700,
        ]);
        $this->assertDatabaseHas('plan_module_usage_tiers', [
            'plan_catalog_version_id' => $pro->versions()->where('version', 2)->value('id'),
            'included_quantity' => 500,
            'overage_price_per_unit' => 400,
        ]);
    }

    public function test_defaults_publish_consumption_tiers_for_each_configured_plan(): void
    {
        foreach (['basic', 'pro', 'enterprise'] as $planCode) {
            $this->createPlanWithPublishedTier($planCode);
        }

        foreach (['kds', 'kitchen-printer', 'waiter-app', 'waiter-printer', 'self-ordering', 'self-ordering-printer'] as $moduleCode) {
            ModuleCatalog::factory()->create(['code' => $moduleCode]);
        }

        $effectiveFrom = today()->addMonth()->startOfMonth();

        $this->artisan('billing:update-prices', [
            '--defaults' => true,
            '--effective-from' => $effectiveFrom->toDateString(),
            '--force' => true,
        ])->assertSuccessful();

        $basicVersion = PlanCatalog::query()
            ->where('code', 'basic')
            ->firstOrFail()
            ->versions()
            ->where('version', 2)
            ->firstOrFail();

        $this->assertSame(14900, $basicVersion->minimum_monthly_price);
        $this->assertSame(12, $basicVersion->usageTiers()->count());
        $this->assertDatabaseHas('plan_module_usage_tiers', [
            'plan_catalog_version_id' => $basicVersion->id,
            'module_code' => 'kds',
            'min_quantity' => 0,
            'included_quantity' => 100,
            'overage_price_per_unit' => 500,
        ]);
        $this->assertDatabaseCount('plan_module_usage_tiers', 39);
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

    private function createPlanWithPublishedTier(string $code): PlanCatalog
    {
        $plan = PlanCatalog::factory()->create(['code' => $code]);
        $version = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $plan->id,
            'effective_from' => '2026-01-01',
        ]);
        PlanModuleUsageTier::factory()->create([
            'plan_catalog_version_id' => $version->id,
        ]);

        return $plan;
    }
}
