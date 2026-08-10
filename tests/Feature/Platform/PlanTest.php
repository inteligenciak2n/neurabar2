<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
use App\Models\AuditLog;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
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
}
