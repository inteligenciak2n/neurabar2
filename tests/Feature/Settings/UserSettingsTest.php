<?php

namespace Tests\Feature\Settings;

use App\Enums\PreferredPhoneHand;
use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class UserSettingsTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_list_users(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('settings.users.index'))->assertOk();
    }

    public function test_owner_can_create_operational_user(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.users.store'), [
            'name' => 'New Attendant',
            'email' => 'attendant@test.com',
            'password' => 'password',
            'role' => 'attendant',
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'attendant@test.com',
        ]);

        $this->assertDatabaseHas('user_venue', [
            'role' => 'attendant',
            'venue_id' => $venue->id,
        ]);

        $this->assertSame(
            PreferredPhoneHand::Right,
            User::query()->where('email', 'attendant@test.com')->first()?->preferred_phone_hand,
        );
    }

    public function test_blank_preferred_phone_hand_defaults_to_right(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.users.store'), [
            'name' => 'Right Hand Attendant',
            'email' => 'righthand@test.com',
            'password' => 'password',
            'role' => 'attendant',
            'preferred_phone_hand' => '',
            'active' => true,
        ])->assertRedirect();

        $this->assertSame(
            PreferredPhoneHand::Right,
            User::query()->where('email', 'righthand@test.com')->first()?->preferred_phone_hand,
        );
    }

    public function test_owner_can_create_a_left_handed_user(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.users.store'), [
            'name' => 'Left Hand Attendant',
            'email' => 'lefthand@test.com',
            'password' => 'password',
            'role' => 'attendant',
            'preferred_phone_hand' => PreferredPhoneHand::Left->value,
            'active' => true,
        ])->assertRedirect();

        $this->assertSame(
            PreferredPhoneHand::Left,
            User::query()->where('email', 'lefthand@test.com')->first()?->preferred_phone_hand,
        );
    }

    public function test_owner_can_update_preferred_phone_hand(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $attendant = User::factory()->create([
            'active' => true,
            'current_venue_id' => $venue->id,
            'preferred_phone_hand' => PreferredPhoneHand::Right,
        ]);
        $venue->users()->attach($attendant->id, ['role' => UserRole::Attendant->value]);

        $this->put(route('settings.users.update', $attendant), [
            'name' => $attendant->name,
            'email' => $attendant->email,
            'role' => UserRole::Attendant->value,
            'preferred_phone_hand' => PreferredPhoneHand::Left->value,
            'active' => true,
        ])->assertRedirect();

        $this->assertSame(PreferredPhoneHand::Left, $attendant->fresh()->preferred_phone_hand);
    }

    public function test_users_page_includes_preferred_phone_hand(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('settings.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Users')
                ->where('users.0.preferred_phone_hand', PreferredPhoneHand::Right->value)
            );
    }

    public function test_invalid_preferred_phone_hand_is_rejected(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.users.store'), [
            'name' => 'Invalid Hand',
            'email' => 'invalidhand@test.com',
            'password' => 'password',
            'role' => 'attendant',
            'preferred_phone_hand' => 'both',
            'active' => true,
        ])->assertSessionHasErrors('preferred_phone_hand');

        $this->assertDatabaseMissing('users', ['email' => 'invalidhand@test.com']);
    }

    public function test_owner_cannot_create_super_admin(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        // Platform profiles no longer exist in UserRole, so the venue role
        // validation itself rejects the value before it reaches the action.
        $this->post(route('settings.users.store'), [
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => 'password',
            'role' => 'super_admin',
            'active' => true,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'super@test.com']);
    }

    public function test_owner_can_delete_attendant(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $attendant = User::factory()->create(['active' => true, 'current_venue_id' => $venue->id]);
        $venue->users()->attach($attendant->id, ['role' => UserRole::Attendant->value]);

        $this->delete(route('settings.users.destroy', $attendant->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('user_venue', ['user_id' => $attendant->id, 'venue_id' => $venue->id]);
    }

    public function test_attendant_cannot_manage_users(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('settings.users.index'))->assertForbidden();
    }
}
