<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\Platform\PlatformUser;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Login as a venue user with the given role, optionally tied to a specific venue.
     */
    protected function loginAs(UserRole $role, ?Venue $venue = null): User
    {
        $venue ??= Venue::factory()->create();

        $user = User::factory()->create([
            'role' => $role,
            'venue_id' => $venue->id,
            'corporation_id' => $venue->corporation_id,
        ]);

        $this->actingAs($user);

        app()->instance('tenant', $venue);

        return $user;
    }

    /**
     * Login as a platform-level user with the given role.
     */
    protected function loginAsPlatformUser(UserRole $role): PlatformUser
    {
        $platformUser = PlatformUser::factory()->create(['role' => $role]);

        $this->actingAs($platformUser, 'platform');

        return $platformUser;
    }
}
