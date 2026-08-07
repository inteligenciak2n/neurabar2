<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $venue = Venue::factory()->create();
        $user = User::factory()->create([
            'email' => 'owner@test.com',
            'current_venue_id' => $venue->id,
            'active' => true,
            'onboarding_completed_at' => now(),
        ]);
        $venue->users()->attach($user->id, ['role' => UserRole::Owner->value]);

        $response = $this->post('/login', [
            'email' => 'owner@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $venue = Venue::factory()->create();
        User::factory()->create([
            'email' => 'inactive@test.com',
            'current_venue_id' => $venue->id,
            'active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $venue = Venue::factory()->create();
        User::factory()->create([
            'email' => 'user@test.com',
            'current_venue_id' => $venue->id,
            'active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $venue = Venue::factory()->create();
        $user = User::factory()->create([
            'current_venue_id' => $venue->id,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
