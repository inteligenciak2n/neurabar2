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
            'current_venue_id' => $venue->id,
            'active' => true,
        ]);
        $venue->users()->attach($user->id, ['role' => UserRole::Attendant->value]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->get('/dashboard')->assertOk();
    }

    public function test_user_without_venue_gets_redirected_to_no_venue(): void
    {
        $user = User::factory()->create([
            'current_venue_id' => null,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('no-venue.index'));
    }

    public function test_owner_can_switch_venue(): void
    {
        $corporation = Corporation::factory()->create();
        $venueA = Venue::factory()->create(['corporation_id' => $corporation->id, 'active' => true]);
        $venueB = Venue::factory()->create(['corporation_id' => $corporation->id, 'active' => true]);

        $user = User::factory()->create([
            'current_venue_id' => $venueA->id,
            'active' => true,
        ]);

        $venue_A_id = $venueA->id;
        $venue_B_id = $venueB->id;
        $user_id = $user->id;

        $venueA->users()->attach($user_id, ['role' => UserRole::Owner->value]);
        $venueB->users()->attach($user_id, ['role' => UserRole::Owner->value]);

        $this->actingAs($user)
            ->post("/account/venue/{$venue_B_id}")
            ->assertRedirect('/dashboard');

        $this->assertEquals($venue_B_id, $user->fresh()->current_venue_id);
    }

    public function test_user_cannot_switch_to_venue_they_are_not_member_of(): void
    {
        $venueA = Venue::factory()->create(['active' => true]);
        $otherVenue = Venue::factory()->create(['active' => true]);

        $user = User::factory()->create([
            'current_venue_id' => $venueA->id,
            'active' => true,
        ]);

        $venueA->users()->attach($user->id, ['role' => UserRole::Owner->value]);

        $this->actingAs($user)
            ->post("/account/venue/{$otherVenue->id}")
            ->assertForbidden();
    }
}
