<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class GeneralSettingsLayoutTest extends TestCase
{
    public function test_general_settings_save_automatically_without_a_save_button(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/General.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('useAutosaveForm(form, submit)', $page);
        $this->assertStringContainsString('preserveScroll: true', $page);
        $this->assertStringNotContainsString("__('Save Changes')", $page);
        $this->assertStringNotContainsString('AppButton', $page);
    }
}
