<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class SettingsSectionHeadersTest extends TestCase
{
    public function test_settings_pages_put_the_section_description_beside_the_title(): void
    {
        $header = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/SettingsSectionHeader.vue');

        $this->assertNotFalse($header);
        $this->assertStringContainsString('flex min-w-0 items-center gap-3', $header);
        $this->assertStringContainsString('shrink-0 font-heading text-2xl font-bold', $header);
        $this->assertStringContainsString('text-sm leading-snug text-muted-foreground', $header);

        $pages = [
            'resources/js/Pages/Settings/AttendanceChannels.vue' => 'Choose how the customer is served',
            'resources/js/Pages/Settings/KitchenStations.vue' => 'Manage the prep stations',
            'resources/js/Pages/Settings/PreparationStatuses.vue' => 'Define the stages of preparation',
            'resources/js/Pages/Settings/ServiceLocations.vue' => 'Tables, counters and areas',
            'resources/js/Pages/Settings/Users.vue' => 'Staff accounts for this venue',
            'resources/js/Pages/Settings/General.vue' => 'Cover charge and service fee',
            'resources/js/Pages/Settings/Venue.vue' => 'Name, address and contact',
            'resources/js/Pages/Settings/Index.vue' => 'Configure the system to best serve your establishment.',
            'resources/js/Pages/Settings/Subscription/Index.vue' => 'Save more time by subscribing to more modules',
        ];

        foreach ($pages as $path => $description) {
            $page = file_get_contents(dirname(__DIR__, 3).'/'.$path);

            $this->assertNotFalse($page, $path);
            $this->assertStringContainsString('SettingsSectionHeader', $page, $path);
            $this->assertStringContainsString($description, $page, $path);
        }
    }
}
