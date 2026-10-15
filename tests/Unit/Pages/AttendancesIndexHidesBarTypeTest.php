<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class AttendancesIndexHidesBarTypeTest extends TestCase
{
    public function test_new_attendance_form_does_not_group_bar_service_locations(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Attendances/Index.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString("const hiddenServiceLocationTypes = ['bar']", $page);
        $this->assertStringContainsString('selectableServiceLocations()', $page);
        $this->assertStringNotContainsString('ungroupedServiceLocations()', $page);
        $this->assertStringContainsString('serviceLocationsByType()', $page);
    }
}
