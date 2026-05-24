<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_venue_settings(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.venue'))->assertOk();
    }

    public function test_general_manager_can_view_venue_settings(): void
    {
        $this->loginAs(UserRole::GeneralManager);

        $this->get(route('settings.venue'))->assertOk();
    }

    public function test_attendant_cannot_access_venue_settings(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('settings.venue'))->assertForbidden();
    }

    public function test_owner_can_update_venue(): void
    {
        $venue = Venue::factory()->create(['name' => 'Old Name', 'active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->put(route('settings.venue.update'), [
            'name' => 'New Name',
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'name' => 'New Name']);
    }

    public function test_venue_name_is_required(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->put(route('settings.venue.update'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_duplicate_call_waiter_slug_returns_validation_error(): void
    {
        Venue::factory()->create(['call_waiter_slug' => 'taken-slug']);

        $venue = Venue::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->put(route('settings.venue.update'), [
            'name' => 'My Venue',
            'call_waiter_slug' => 'taken-slug',
        ])->assertSessionHasErrors('call_waiter_slug');
    }

    public function test_same_venue_can_keep_its_own_slug(): void
    {
        $venue = Venue::factory()->create(['call_waiter_slug' => 'my-slug', 'active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->put(route('settings.venue.update'), [
            'name' => 'My Venue',
            'call_waiter_slug' => 'my-slug',
        ])->assertRedirect();
    }
}
