<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class ServiceLocationsLayoutTest extends TestCase
{
    public function test_standalone_service_locations_settings_were_moved_into_channels(): void
    {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root.'/resources/js/Pages/Settings/Index.vue');
        $channels = file_get_contents($root.'/resources/js/Pages/Settings/AttendanceChannels.vue');

        $this->assertFileDoesNotExist($root.'/resources/js/Pages/Settings/ServiceLocations.vue');
        $this->assertNotFalse($index);
        $this->assertNotFalse($channels);
        $this->assertStringNotContainsString('settings.service-locations.index', $index);
        $this->assertStringNotContainsString("__('Service Locations')", $index);
        $this->assertStringContainsString('settings.service-locations.range', $channels);
        $this->assertStringContainsString('settings.service-locations.qr', $channels);
        $this->assertStringContainsString('inactivateLocation', $channels);
    }
}
