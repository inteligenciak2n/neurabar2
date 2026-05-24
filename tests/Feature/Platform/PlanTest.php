<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Models\Tenant\PlanCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_user_can_view_plans(): void
    {
        $this->loginAsPlatformUser(UserRole::Finance);

        $this->get(route('platform.plans.index'))->assertOk();
    }

    public function test_super_admin_can_create_plan(): void
    {
        $this->loginAsPlatformUser(UserRole::SuperAdmin);

        $this->post(route('platform.plans.store'), [
            'code' => 'STARTER',
            'name' => 'Starter',
            'monthly_price' => 99.00,
            'sort_order' => 1,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('plan_catalogs', ['code' => 'STARTER']);
    }

    public function test_super_admin_can_update_plan(): void
    {
        $this->loginAsPlatformUser(UserRole::SuperAdmin);

        $plan = PlanCatalog::factory()->create(['monthly_price' => 99.00]);

        $this->put(route('platform.plans.update', $plan->id), [
            'code' => $plan->code,
            'name' => 'Updated Name',
            'monthly_price' => 149.00,
            'sort_order' => 1,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('plan_catalogs', ['id' => $plan->id, 'monthly_price' => 149.00]);
    }

    public function test_super_admin_can_delete_plan(): void
    {
        $this->loginAsPlatformUser(UserRole::SuperAdmin);

        $plan = PlanCatalog::factory()->create();

        $this->delete(route('platform.plans.destroy', $plan->id))->assertRedirect();

        $this->assertDatabaseMissing('plan_catalogs', ['id' => $plan->id]);
    }
}
