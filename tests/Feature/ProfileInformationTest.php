<?php

namespace Tests\Feature;

use App\Enums\PreferredPhoneHand;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => 'test@example.com',
        ]);

        $this->assertEquals('Test Name', $user->fresh()->name);
        $this->assertEquals('test@example.com', $user->fresh()->email);
    }

    public function test_preferred_phone_hand_can_be_updated_from_the_profile(): void
    {
        $this->actingAs($user = User::factory()->create([
            'preferred_phone_hand' => PreferredPhoneHand::Right,
        ]));

        $this->put('/user/profile-information', [
            'name' => $user->name,
            'email' => $user->email,
            'preferred_phone_hand' => PreferredPhoneHand::Left->value,
        ]);

        $this->assertSame(PreferredPhoneHand::Left, $user->fresh()->preferred_phone_hand);
    }

    public function test_blank_preferred_phone_hand_on_the_profile_defaults_to_right(): void
    {
        $this->actingAs($user = User::factory()->create([
            'preferred_phone_hand' => PreferredPhoneHand::Left,
        ]));

        $this->put('/user/profile-information', [
            'name' => $user->name,
            'email' => $user->email,
            'preferred_phone_hand' => '',
        ]);

        $this->assertSame(PreferredPhoneHand::Right, $user->fresh()->preferred_phone_hand);
    }

    public function test_omitting_preferred_phone_hand_keeps_the_current_value(): void
    {
        $this->actingAs($user = User::factory()->create([
            'preferred_phone_hand' => PreferredPhoneHand::Left,
        ]));

        $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => 'test@example.com',
        ]);

        $this->assertSame(PreferredPhoneHand::Left, $user->fresh()->preferred_phone_hand);
    }
}
