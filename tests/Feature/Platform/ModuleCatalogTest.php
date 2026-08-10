<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
use App\Models\AuditLog;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\ModuleCatalog;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ModuleCatalogTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_platform_user_can_view_modules(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::ReadOnly);

        $this->get(route('platform.modules.index'))->assertOk();
    }

    public function test_finance_user_can_create_module(): void
    {
        $actor = $this->loginAsPlatformUser(ProfileEnum::Finance);
        ModuleCatalog::factory()->create(['code' => 'menu']);

        $this->post(route('platform.modules.store'), [
            'code' => 'kds',
            'name' => 'Kitchen Display',
            'description' => 'Kitchen operation display.',
            'category' => 'premium',
            'billing_type' => 'hybrid',
            'base_monthly_price' => 49.90,
            'unit_of_measure' => 'order',
            'dependencies' => ['menu'],
            'required_roles' => ['owner', 'general_manager'],
            'icon' => 'monitor',
            'sort_order' => 10,
            'active' => true,
        ])->assertRedirect();

        $module = ModuleCatalog::query()->where('code', 'kds')->firstOrFail();

        $this->assertSame(4990, $module->base_monthly_price);
        $this->assertSame(['menu'], $module->dependencies);
        $this->assertSame(['owner', 'general_manager'], $module->required_roles);

        $log = AuditLog::query()->where('action', 'module_catalog.created')->firstOrFail();
        $this->assertSame($module->id, $log->auditable_id);
        $this->assertSame($actor->id, $log->actor_id);
    }

    public function test_super_admin_can_update_module_without_changing_its_code(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);
        $module = ModuleCatalog::factory()->create(['code' => 'kds']);

        $this->put(route('platform.modules.update', $module), [
            'code' => 'kds',
            'name' => 'Updated KDS',
            'category' => 'premium',
            'billing_type' => 'fixed',
            'base_monthly_price' => 59.90,
            'dependencies' => [],
            'required_roles' => ['owner'],
            'sort_order' => 20,
            'active' => false,
        ])->assertRedirect();

        $module->refresh();
        $this->assertSame('Updated KDS', $module->name);
        $this->assertSame(5990, $module->base_monthly_price);
        $this->assertFalse($module->active);
    }

    public function test_module_code_cannot_be_changed(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);
        $module = ModuleCatalog::factory()->create(['code' => 'kds']);

        $this->put(route('platform.modules.update', $module), [
            'code' => 'taker',
            'name' => $module->name,
            'category' => $module->category,
            'billing_type' => 'fixed',
            'base_monthly_price' => 0,
            'dependencies' => [],
            'required_roles' => [],
            'sort_order' => 1,
            'active' => true,
        ])->assertSessionHasErrors('code');
    }

    public function test_unused_module_can_be_deleted(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);
        $module = ModuleCatalog::factory()->create(['code' => 'voice_command']);

        $this->delete(route('platform.modules.destroy', $module))->assertRedirect();

        $this->assertModelMissing($module);
    }

    public function test_module_in_use_cannot_be_deleted(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);
        $module = ModuleCatalog::factory()->create(['code' => 'kds']);
        CorporationModule::factory()->create(['module_code' => $module->code]);

        $this->delete(route('platform.modules.destroy', $module))
            ->assertRedirect()
            ->assertSessionHasErrors('module');

        $this->assertModelExists($module);
    }
}
