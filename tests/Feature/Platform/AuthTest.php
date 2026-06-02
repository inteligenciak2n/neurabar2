<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_backoffice_dashboard_requires_authentication(): void
    {
        $this->get(route('platform.dashboard'))->assertRedirect(route('login'));
    }

    public function test_platform_user_can_access_backoffice_dashboard(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $this->get(route('platform.dashboard'))->assertOk();
    }

    public function test_client_user_is_forbidden_from_backoffice(): void
    {
        $user = User::factory()->create(['profile' => ProfileEnum::Client]);

        $this->actingAs($user);

        $this->get(route('platform.dashboard'))->assertForbidden();
    }

    public function test_client_user_is_forbidden_from_backoffice_corporations(): void
    {
        $user = User::factory()->create(['profile' => ProfileEnum::Client]);

        $this->actingAs($user);

        $this->get(route('platform.corporations.index'))->assertForbidden();
    }

    public function test_platform_user_can_logout(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $this->post(route('logout'))->assertRedirect();

        $this->assertGuest();
    }
}
