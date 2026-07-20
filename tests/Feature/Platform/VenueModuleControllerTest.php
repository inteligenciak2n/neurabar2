<?php

namespace Tests\Feature\Platform;

use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class VenueModuleControllerTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_platform_user_can_list_venue_modules(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        $venue = Venue::factory()->create();
        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $response = $this->actingAs($user)->get(route('platform.corporations.venues.modules.index', [$venue->corporation, $venue]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Platform/Corporations/Venues/Modules/Index')
            ->has('modules.data', 1)
        );
    }

    public function test_platform_user_can_activate_module_for_venue(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        $venue = Venue::factory()->create();
        $corporation = $venue->corporation;
        ModuleCatalog::updateOrCreate(
            ['code' => ModuleCode::Kds->value],
            [
                'name' => ModuleCode::Kds->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 49.90,
                'active' => true,
            ]
        );
        CorporationModule::factory()->create([
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $response = $this->actingAs($user)->post(route('platform.corporations.venues.modules.store', [$corporation, $venue]), [
            'module_code' => ModuleCode::Kds->value,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('venue_modules', [
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active->value,
            'quantity' => 2,
        ]);
    }

    public function test_platform_user_can_deactivate_module_for_venue(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        $venue = Venue::factory()->create();
        $module = VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $response = $this->actingAs($user)->delete(route('platform.corporations.venues.modules.destroy', [$venue->corporation, $venue, $module]));

        $response->assertRedirect();
        $this->assertDatabaseHas('venue_modules', [
            'id' => $module->id,
            'status' => ModuleStatus::Inactive->value,
        ]);
    }

    public function test_cannot_access_venue_module_from_different_corporation(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        $venue = Venue::factory()->create();
        $otherCorporation = Corporation::factory()->create();
        $module = VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $this->actingAs($user)
            ->get(route('platform.corporations.venues.modules.index', [$otherCorporation, $venue]))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('platform.corporations.venues.modules.store', [$otherCorporation, $venue]), [
                'module_code' => ModuleCode::Kds->value,
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('platform.corporations.venues.modules.destroy', [$otherCorporation, $venue, $module]))
            ->assertNotFound();
    }
}
