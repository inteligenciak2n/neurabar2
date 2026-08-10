<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
use App\Models\AuditLog;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_backoffice_user_can_view_plans(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::Finance);

        $this->get(route('platform.plans.index'))->assertOk();
    }

    public function test_super_admin_can_create_plan(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        ModuleCatalog::factory()->create(['code' => 'menu']);
        ModuleCatalog::factory()->create(['code' => 'kds']);

        $this->post(route('platform.plans.store'), [
            'code' => 'STARTER',
            'name' => 'Starter',
            'monthly_price' => 99.00,
            'dedicated_surcharge' => 50.00,
            'plan_type' => 'dedicated',
            'included_modules' => ['menu', 'kds'],
            'sort_order' => 1,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('plan_catalogs', [
            'code' => 'STARTER',
            'monthly_price' => 9900,
            'dedicated_surcharge' => 5000,
            'plan_type' => 'dedicated',
            'sort_order' => 1,
        ]);

        $this->assertSame(
            ['menu', 'kds'],
            PlanCatalog::query()->where('code', 'STARTER')->firstOrFail()->included_modules,
        );
    }

    public function test_super_admin_can_update_plan(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $plan = PlanCatalog::factory()->create(['monthly_price' => 9900]);

        $this->put(route('platform.plans.update', $plan->id), [
            'code' => $plan->code,
            'name' => 'Updated Name',
            // O formulário envia reais; o catálogo guarda centavos.
            'monthly_price' => 149.00,
            'plan_type' => 'shared',
            'included_modules' => [],
            'sort_order' => 1,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('plan_catalogs', ['id' => $plan->id, 'monthly_price' => 14900]);
    }

    public function test_super_admin_can_delete_plan(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $plan = PlanCatalog::factory()->create();

        $this->delete(route('platform.plans.destroy', $plan->id))->assertRedirect();

        $this->assertDatabaseMissing('plan_catalogs', ['id' => $plan->id]);
    }

    public function test_updating_a_plan_price_is_audited(): void
    {
        $actor = $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $plan = PlanCatalog::factory()->create(['monthly_price' => 9900]);

        $this->put(route('platform.plans.update', $plan->id), [
            'code' => $plan->code,
            'name' => $plan->name,
            'monthly_price' => 149.00,
            'plan_type' => 'shared',
            'included_modules' => [],
            'sort_order' => 1,
            'active' => true,
        ])->assertRedirect();

        $log = AuditLog::query()->where('action', 'plan.updated')->firstOrFail();

        $this->assertSame($plan->id, $log->auditable_id);
        $this->assertSame($actor->id, $log->actor_id);
        $this->assertSame(9900, $log->before['monthly_price']);
        $this->assertSame(14900, $log->after['monthly_price']);
    }

    public function test_finance_can_create_and_publish_a_plan_pricing_version(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::Finance);
        $plan = PlanCatalog::factory()->create();

        $this->post(route('platform.plans.usage-pricing.store', $plan), [
            'effective_from' => today()->addMonth()->startOfMonth()->format('Y-m-d'),
            'minimum_monthly_price' => 249.00,
            'infrastructure_type' => 'shared',
            'currency' => 'BRL',
            'tiers' => [[
                'module_code' => 'kds',
                'min_quantity' => 0,
                'max_quantity' => null,
                'included_quantity' => 500,
                'price_per_unit' => 0,
                'flat_price' => null,
                'overage_price_per_unit' => 0.05,
                'overage_flat_fee' => null,
            ]],
        ])->assertRedirect();

        $version = PlanCatalogVersion::query()->where('plan_catalog_id', $plan->id)->firstOrFail();

        $this->assertSame(24900, $version->minimum_monthly_price);
        $this->assertDatabaseHas('plan_module_usage_tiers', [
            'plan_catalog_version_id' => $version->id,
            'module_code' => 'kds',
            'included_quantity' => 500,
            'overage_price_per_unit' => 500,
        ]);

        $this->post(route('platform.plans.usage-pricing.publish', [$plan, $version]))->assertRedirect();

        $this->assertDatabaseHas('plan_catalog_versions', ['id' => $version->id, 'status' => 'published']);
    }

    public function test_plan_pricing_rejects_gaps_between_tiers(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::Finance);
        $plan = PlanCatalog::factory()->create();

        $this->post(route('platform.plans.usage-pricing.store', $plan), [
            'effective_from' => today()->addMonth()->startOfMonth()->format('Y-m-d'),
            'minimum_monthly_price' => 249.00,
            'infrastructure_type' => 'shared',
            'currency' => 'BRL',
            'tiers' => [
                ['module_code' => 'kds', 'min_quantity' => 0, 'max_quantity' => 100, 'included_quantity' => 100, 'price_per_unit' => 0, 'overage_price_per_unit' => 0.05],
                ['module_code' => 'kds', 'min_quantity' => 102, 'max_quantity' => null, 'included_quantity' => 0, 'price_per_unit' => 0, 'overage_price_per_unit' => 0.03],
            ],
        ])->assertSessionHasErrors('tiers');

        $this->assertDatabaseMissing('plan_catalog_versions', ['plan_catalog_id' => $plan->id]);
    }
}
