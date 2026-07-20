<?php

namespace Tests\Feature\Module;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use App\Models\User;
use App\Services\VenueModuleCache;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class UserCanAccessModuleTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_blocks_access_without_current_venue(): void
    {
        $user = User::factory()->create(['current_venue_id' => null]);

        $this->assertFalse($user->canAccessModule(ModuleCode::Menu));
    }

    public function test_blocks_access_when_module_is_not_active_for_venue(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);

        $user = User::factory()->create(['current_venue_id' => $venue->id]);

        $this->assertFalse($user->canAccessModule(ModuleCode::Kds));
    }

    public function test_blocks_access_when_billing_is_blocked(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);
        $this->activateModuleForVenue($venue, ModuleCode::Kds);

        CorporationSubscription::query()
            ->where('corporation_id', $venue->corporation_id)
            ->firstOrFail()
            ->update(['status' => SubscriptionStatus::Suspended]);

        VenueSubscription::query()
            ->where('venue_id', $venue->id)
            ->firstOrFail()
            ->update(['status' => SubscriptionStatus::Suspended]);

        $user = User::factory()->create(['current_venue_id' => $venue->id]);

        $this->assertFalse($user->canAccessModule(ModuleCode::Kds));
    }

    public function test_blocks_access_when_corporation_does_not_have_module(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        $user = User::factory()->create(['current_venue_id' => $venue->id]);

        $this->assertFalse($user->canAccessModule(ModuleCode::Kds));
    }

    public function test_blocks_access_when_dependency_is_missing(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);
        $this->activateModuleForVenue($venue, ModuleCode::Kds);

        VenueModule::query()
            ->where('venue_id', $venue->id)
            ->where('module_code', ModuleCode::Menu->value)
            ->update(['status' => ModuleStatus::Inactive]);

        VenueModuleCache::forget($venue);

        $user = User::factory()->create(['current_venue_id' => $venue->id]);

        $this->assertFalse($user->canAccessModule(ModuleCode::Kds));
    }

    public function test_allows_access_when_all_requirements_are_met(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);
        $this->activateModuleForVenue($venue, ModuleCode::Kds);

        $user = User::factory()->create(['current_venue_id' => $venue->id]);

        $this->assertTrue($user->canAccessModule(ModuleCode::Kds));
    }

    private function activateModuleForVenue(Venue $venue, ModuleCode $code): void
    {
        if (! $venue->corporation->modules()->where('module_code', $code->value)->exists()) {
            CorporationModule::factory()->create([
                'corporation_id' => $venue->corporation_id,
                'module_code' => $code->value,
                'status' => ModuleStatus::Active,
            ]);
        }

        if (! $venue->modules()->where('module_code', $code->value)->exists()) {
            VenueModule::factory()->create([
                'venue_id' => $venue->id,
                'module_code' => $code->value,
                'status' => ModuleStatus::Active,
            ]);
        }
    }
}
