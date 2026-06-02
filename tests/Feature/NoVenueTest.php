<?php

namespace Tests\Feature;

use App\Models\Tenant\Corporation;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class NoVenueTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_user_without_venue_is_redirected_to_no_venue_page(): void
    {
        $user = User::factory()->create([
            'current_venue_id' => null,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('no-venue.index'));
    }

    public function test_no_venue_page_is_accessible_when_user_has_no_venue(): void
    {
        $user = User::factory()->create([
            'current_venue_id' => null,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('no-venue.index'))
            ->assertOk();
    }

    public function test_owner_can_create_first_venue_from_no_venue_page(): void
    {
        $corporation = Corporation::factory()->create();
        $user = User::factory()->create([
            'current_venue_id' => null,
            'active' => true,
        ]);
        $corporation->update(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('no-venue.store'), ['name' => 'My New Venue'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('venues', ['name' => 'My New Venue']);
        $this->assertNotNull($user->fresh()->current_venue_id);
    }
}
