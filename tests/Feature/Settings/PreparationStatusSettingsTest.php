<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Settings\PreparationStatus;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreparationStatusSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_preparation_statuses(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.preparation-statuses.index'))->assertOk();
    }

    public function test_owner_can_create_preparation_status(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->post(route('settings.preparation-statuses.store'), [
            'name' => 'In Progress',
            'color' => '#f59e0b',
            'sort_order' => 1,
            'show_to_customer' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('preparation_statuses', [
            'venue_id' => $venue->id,
            'name' => 'In Progress',
            'color' => '#f59e0b',
            'show_to_customer' => true,
        ]);
    }

    public function test_owner_can_update_preparation_status(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);
        $status = PreparationStatus::factory()->create(['venue_id' => $venue->id]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->put(route('settings.preparation-statuses.update', $status->id), [
            'name' => 'Ready',
            'color' => '#22c55e',
            'sort_order' => 2,
            'show_to_customer' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('preparation_statuses', [
            'id' => $status->id,
            'name' => 'Ready',
            'color' => '#22c55e',
        ]);
    }

    public function test_owner_can_delete_preparation_status(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);
        $status = PreparationStatus::factory()->create(['venue_id' => $venue->id]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->delete(route('settings.preparation-statuses.destroy', $status->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('preparation_statuses', ['id' => $status->id]);
    }

    public function test_invalid_hex_color_returns_validation_error(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->post(route('settings.preparation-statuses.store'), [
            'name' => 'Pending',
            'color' => 'not-a-color',
        ])->assertSessionHasErrors('color');
    }

    public function test_status_name_is_required(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $this->post(route('settings.preparation-statuses.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_status_is_scoped_to_tenant(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $otherVenue = Venue::factory()->create(['active' => true]);

        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);
        PreparationStatus::factory()->create(['venue_id' => $otherVenue->id, 'name' => 'OtherStatus']);

        $this->actingAs($user);
        app()->instance('tenant', $venue);

        $response = $this->get(route('settings.preparation-statuses.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('statuses', fn ($statuses) => collect($statuses)->every(
                fn ($s) => $s['venue_id'] === $venue->id
            ))
        );
    }

    public function test_attendant_cannot_manage_preparation_statuses(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('settings.preparation-statuses.index'))->assertForbidden();
    }
}
