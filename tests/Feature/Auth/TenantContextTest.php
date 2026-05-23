<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_user_can_access_dashboard_with_venue_context(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Attendant,
            'venue_id' => $venue->id,
            'active' => true,
        ]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->get('/dashboard')->assertOk();
    }

    public function test_platform_user_gets_403_on_operational_routes(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'venue_id' => null,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_corporation_admin_can_switch_venue(): void
    {
        $corporation = Corporation::factory()->create();
        $venueA = Venue::factory()->create(['corporation_id' => $corporation->id, 'active' => true]);
        $venueB = Venue::factory()->create(['corporation_id' => $corporation->id, 'active' => true]);

        $user = User::factory()->create([
            'role' => UserRole::CorporationAdmin,
            'corporation_id' => $corporation->id,
            'venue_id' => $venueA->id,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['active_venue_id' => $venueA->id])
            ->post("/account/venue/{$venueB->id}")
            ->assertRedirect('/dashboard');

        $this->assertEquals($venueB->id, session('active_venue_id'));
    }

    public function test_corporation_admin_cannot_switch_to_venue_from_another_corporation(): void
    {
        $corporation = Corporation::factory()->create();
        $otherVenue = Venue::factory()->create(['active' => true]);

        $user = User::factory()->create([
            'role' => UserRole::CorporationAdmin,
            'corporation_id' => $corporation->id,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post("/account/venue/{$otherVenue->id}")
            ->assertNotFound();
    }

    public function test_user_without_venue_gets_403(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Attendant,
            'venue_id' => null,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }
}
