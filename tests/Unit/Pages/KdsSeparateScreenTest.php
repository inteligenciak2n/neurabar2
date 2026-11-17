<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class KdsSeparateScreenTest extends TestCase
{
    public function test_kds_opens_a_station_picker_before_a_separate_monitor(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Kitchen/Kds.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('data-open-separate-screen', $page);
        $this->assertStringContainsString("__('Open separate screen')", $page);
        $this->assertStringContainsString('data-station-picker', $page);
        $this->assertStringContainsString("__('Which stations do you want on this screen?')", $page);
        $this->assertStringContainsString('selectableStations', $page);
        $this->assertStringContainsString("window.open(dedicatedScreenUrl(draftStationIds.value), '_blank', 'noopener')", $page);
        $this->assertStringContainsString('data-manage-stations', $page);
        $this->assertStringContainsString("__('Add or remove stations')", $page);
        $this->assertStringContainsString('display: 1', $page);
        $this->assertStringContainsString('isDedicatedDisplay', $page);
        $this->assertStringContainsString('toggleDraftStation', $page);
        $this->assertStringContainsString('stationLabel(station)', $page);
    }
}
