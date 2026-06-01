<?php

namespace Tests;

use App\Enums\ProfileEnum;
use App\Enums\UserRole;
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
            'current_venue_id' => $venue->id,
        ]);

        $venue->users()->attach($user->id, ['role' => $role->value]);

        $this->actingAs($user);

        app()->instance('tenant', $venue);

        return $user;
    }

    /**
     * Login as a platform-level user with the given profile.
     */
    protected function loginAsPlatformUser(ProfileEnum $profile): User
    {
        $user = User::factory()->create(['profile' => $profile]);

        $this->actingAs($user);

        return $user;
    }
}
