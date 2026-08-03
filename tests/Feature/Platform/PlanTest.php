<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
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

        $this->post(route('platform.plans.store'), [
            'code' => 'STARTER',
            'name' => 'Starter',
            'monthly_price' => 99.00,
            'dedicated_surcharge' => 50.00,
            'sort_order' => 1,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('plan_catalogs', [
            'code' => 'STARTER',
            'monthly_price' => 9900,
            'dedicated_surcharge' => 5000,
        ]);
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
}
