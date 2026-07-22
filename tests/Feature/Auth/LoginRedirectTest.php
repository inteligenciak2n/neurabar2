<?php

namespace Tests\Feature\Auth;

use App\Enums\ProfileEnum;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_client_user_is_redirected_to_dashboard_after_login(): void
    {
        User::factory()->create([
            'email' => 'client@test.com',
            'profile' => ProfileEnum::Client,
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'client@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_backoffice_user_is_redirected_to_platform_after_login(): void
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'profile' => ProfileEnum::SuperAdmin,
        ]);

        $platformPath = config('platform.path', 'backoffice');

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(url($platformPath));
    }

    public function test_allbackoffice_profiles_redirect_to_platform_area(): void
    {
        $platformPath = config('platform.path', '_platform');

        foreach (ProfileEnum::platformProfiles() as $profileValue) {
            $profile = ProfileEnum::from($profileValue);

            $user = User::factory()->create([
                'profile' => $profile,
            ]);

            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response->assertRedirect(url($platformPath), "Profile [{$profileValue}] should redirect to platform area.");

            auth()->logout();
        }
    }

    public function test_backoffice_routes_require_platform_profile(): void
    {
        $client = User::factory()->create(['profile' => ProfileEnum::Client]);
        $platformPath = config('platform.path', 'backoffice');

        $this->actingAs($client)
            ->get("/{$platformPath}")
            ->assertForbidden();
    }

    public function test_backoffice_user_can_access_platform_routes(): void
    {
        $admin = User::factory()->create(['profile' => ProfileEnum::SuperAdmin]);
        $platformPath = config('platform.path', 'backoffice');

        $this->actingAs($admin)
            ->get("/{$platformPath}")
            ->assertOk();
    }
}
