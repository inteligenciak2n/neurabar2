<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSettingsTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_owner_cannot_create_super_admin(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('settings.users.store'), [
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => 'password',
            'role' => 'super_admin',
            'active' => true,
        ])->assertForbidden();
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
