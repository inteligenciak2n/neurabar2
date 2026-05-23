<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $venue = Venue::factory()->create();
        $user = User::factory()->create([
            'email' => 'owner@test.com',
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => true,
        ]);

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
            'role' => UserRole::Owner,
            'venue_id' => $venue->id,
            'active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $venue = Venue::factory()->create();
        User::factory()->create([
            'email' => 'user@test.com',
            'role' => UserRole::Attendant,
            'venue_id' => $venue->id,
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
            'role' => UserRole::Attendant,
            'venue_id' => $venue->id,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
