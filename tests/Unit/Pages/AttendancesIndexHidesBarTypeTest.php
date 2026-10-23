<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class AttendancesIndexHidesBarTypeTest extends TestCase
{
    public function test_new_attendance_form_lists_every_service_location_without_type_grouping(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Attendances/Index.vue');

        $this->assertNotFalse($page);
        $this->assertStringNotContainsString('hiddenServiceLocationTypes', $page);
        $this->assertStringNotContainsString('serviceLocationsByType', $page);
        $this->assertStringNotContainsString('loc.type', $page);
        $this->assertStringContainsString('v-for="loc in serviceLocations"', $page);
    }
}
