<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Models\Platform\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_backoffice_login_page_is_accessible(): void
    {
        $this->get(route('platform.login'))->assertOk();
    }

    public function test_backoffice_user_can_login(): void
    {
        $user = PlatformUser::factory()->create([
            'email' => 'admin@platform.test',
            'password' => bcrypt('password'),
            'role' => UserRole::SuperAdmin,
        ]);

        $this->post(route('platform.login.store'), [
            'email' => 'admin@platform.test',
            'password' => 'password',
        ])->assertRedirect(route('platform.dashboard'));

        $this->assertAuthenticatedAs($user, 'platform');
    }

    public function test_backoffice_login_fails_with_wrong_credentials(): void
    {
        PlatformUser::factory()->create(['email' => 'admin@platform.test', 'password' => bcrypt('correct')]);

        $this->post(route('platform.login.store'), [
            'email' => 'admin@platform.test',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');
    }

    public function test_backoffice_dashboard_requires_platform_auth(): void
    {
        $this->get(route('platform.dashboard'))->assertRedirect(route('platform.login'));
    }

    public function test_backoffice_logout_works(): void
    {
        $this->loginAsPlatformUser(UserRole::SuperAdmin);

        $this->post(route('platform.logout'))->assertRedirect(route('platform.login'));

        $this->assertGuest('platform');
    }
}
