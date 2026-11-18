<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class UsersPreferredPhoneHandTest extends TestCase
{
    public function test_users_form_lets_staff_choose_the_preferred_phone_hand(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/Users.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Users.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($page);
        $this->assertStringContainsString("preferred_phone_hand: 'right'", $page);
        $this->assertStringContainsString("__('Preferred phone hand')", $page);
        $this->assertStringContainsString("__('Right')", $page);
        $this->assertStringContainsString("__('Left')", $page);
        $this->assertStringContainsString("user.preferred_phone_hand || 'right'", $page);
        $this->assertSame('Mão que prefere usar o celular', $translations['Preferred phone hand']);
        $this->assertSame('Direita', $translations['Right']);
        $this->assertSame('Esquerda', $translations['Left']);
    }

    public function test_profile_form_lets_the_user_choose_the_preferred_phone_hand(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/UpdateProfileInformationForm.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($page);
        $this->assertStringContainsString("preferred_phone_hand: props.user.preferred_phone_hand === 'left' ? 'left' : 'right'", $page);
        $this->assertStringContainsString("__('Preferred phone hand')", $page);
        $this->assertStringContainsString('id="preferred_phone_hand"', $page);
        $this->assertSame('Mão que prefere usar o celular', $translations['Preferred phone hand']);
        $this->assertSame('Direita', $translations['Right']);
        $this->assertSame('Esquerda', $translations['Left']);
    }
}
