<?php

namespace Tests\Feature\Module;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Orders\Attendance;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use Tests\TestCase;

class RequireModuleMiddlewareTest extends TestCase
{
    private function setupVenueWithMenu(Venue $venue): CorporationSubscription
    {
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'status' => SubscriptionStatus::Active,
        ]);

        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $subscription->id,
            'status' => SubscriptionStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Menu->value,
            'status' => ModuleStatus::Active,
        ]);

        return $subscription;
    }

    public function test_allows_access_when_module_menu_is_active(): void
    {
        $venue = Venue::factory()->create();
        $this->setupVenueWithMenu($venue);

        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_blocks_access_when_module_is_not_active(): void
    {
        $venue = Venue::factory()->create();
        $this->setupVenueWithMenu($venue);

        $attendance = Attendance::factory()->create(['venue_id' => $venue->id]);

        $this->loginAs(UserRole::Attendant, $venue);

        $this->get(route('orders.take', ['attendance' => $attendance->id]))->assertForbidden();
    }

    public function test_blocks_access_when_subscription_is_suspended(): void
    {
        $venue = Venue::factory()->create();
        $this->setupVenueWithMenu($venue);

        $this->loginAs(UserRole::Owner, $venue);

        $venue->corporation->subscription->update(['status' => SubscriptionStatus::Suspended]);
        $venue->subscription->update(['status' => SubscriptionStatus::Suspended]);

        $this->get(route('dashboard'))->assertForbidden();
    }
}
