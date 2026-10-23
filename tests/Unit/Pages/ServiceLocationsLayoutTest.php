<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class ServiceLocationsLayoutTest extends TestCase
{
    public function test_form_hides_type_and_matches_menu_category_layout(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/ServiceLocations.vue');

        $this->assertNotFalse($page);
        $this->assertStringNotContainsString("t('Type')", $page);
        $this->assertStringNotContainsString('locationTypes', $page);
        $this->assertStringNotContainsString('form.type', $page);
        $this->assertStringContainsString('bg-warm-gold/20', $page);
        $this->assertStringContainsString('rounded-lg border-4 border-[#5c9a6c]', $page);
        $this->assertStringContainsString('font-heading text-lg font-bold tracking-tight', $page);
    }
}
