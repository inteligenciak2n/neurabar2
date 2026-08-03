<?php

namespace Tests\Unit;

use App\Enums\ProfileEnum;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class RoleProfileSeparationTest extends TestCase
{
    public function test_platform_profiles_and_venue_roles_never_share_a_value(): void
    {
        $this->assertSame(
            [],
            array_values(array_intersect(ProfileEnum::values(), UserRole::values())),
            'A shared value would let a venue role be mistaken for a platform profile.'
        );
    }

    public function test_user_role_only_contains_operational_roles(): void
    {
        $this->assertSame(
            ['owner', 'general_manager', 'section_manager', 'attendant'],
            UserRole::values()
        );
    }
}
