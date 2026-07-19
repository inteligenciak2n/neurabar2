<?php

namespace Tests\Feature\Platform;

use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\ModuleCatalog;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class CorporationModuleControllerTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_platform_user_can_list_corporation_modules(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        $corporation = Corporation::factory()->create();
        CorporationModule::factory()->create([
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $response = $this->actingAs($user)->get(route('platform.corporations.modules.index', $corporation));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Platform/Corporations/Modules/Index')
            ->has('modules.data', 1)
        );
    }

    public function test_platform_user_can_enable_module_for_corporation(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        $corporation = Corporation::factory()->create();
        ModuleCatalog::updateOrCreate(
            ['code' => ModuleCode::Kds->value],
            [
                'name' => ModuleCode::Kds->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 49.90,
                'active' => true,
            ]
        );

        $response = $this->actingAs($user)->post(route('platform.corporations.modules.store', $corporation), [
            'module_code' => ModuleCode::Kds->value,
            'custom_monthly_price' => 39.90,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('corporation_modules', [
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active->value,
            'custom_monthly_price' => 39.90,
        ]);
    }

    public function test_platform_user_can_disable_module_for_corporation(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        $corporation = Corporation::factory()->create();
        $module = CorporationModule::factory()->create([
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $response = $this->actingAs($user)->delete(route('platform.corporations.modules.destroy', [$corporation, $module]));

        $response->assertRedirect();
        $this->assertDatabaseHas('corporation_modules', [
            'id' => $module->id,
            'status' => ModuleStatus::Inactive->value,
        ]);
    }
}
