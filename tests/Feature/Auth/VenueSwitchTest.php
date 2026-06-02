<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class VenueSwitchTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_user_can_switch_to_a_venue_they_belong_to(): void
    {
        $venueA = Venue::factory()->create(['active' => true]);
        $venueB = Venue::factory()->create(['active' => true]);

        $user = User::factory()->create([
            'current_venue_id' => $venueA->id,
            'active' => true,
        ]);

        $venueA->users()->attach($user->id, ['role' => UserRole::Owner->value]);
        $venueB->users()->attach($user->id, ['role' => UserRole::Owner->value]);

        $this->actingAs($user)
            ->post(route('venue.select', $venueB->id))
            ->assertRedirect(route('dashboard'));

        $this->assertEquals($venueB->id, $user->fresh()->current_venue_id);
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
            ->post(route('venue.select', $otherVenue->id))
            ->assertForbidden();
    }

    public function test_switch_sets_venue_switched_flash(): void
    {
        $venueA = Venue::factory()->create(['active' => true]);
        $venueB = Venue::factory()->create(['active' => true]);

        $user = User::factory()->create([
            'current_venue_id' => $venueA->id,
            'active' => true,
        ]);

        $venueA->users()->attach($user->id, ['role' => UserRole::Owner->value]);
        $venueB->users()->attach($user->id, ['role' => UserRole::Owner->value]);

        $this->actingAs($user)
            ->post(route('venue.select', $venueB->id))
            ->assertSessionHas('venue_switched', true);
    }
}
