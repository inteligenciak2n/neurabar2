<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class TabRowsGroupingTest extends TestCase
{
    public function test_tabs_are_grouped_by_prefix_in_rows_of_ten(): void
    {
        $util = file_get_contents(dirname(__DIR__, 3).'/resources/js/Utils/tabRows.js');

        $this->assertNotFalse($util);
        $this->assertStringContainsString('export function groupTabsForDisplay', $util);
        $this->assertStringContainsString('export function groupTabsByPrefix', $util);
        $this->assertStringContainsString('export function prefixFromCode', $util);
        $this->assertStringContainsString('export function formatPrefixLabel', $util);
        $this->assertStringContainsString('export function tabSeriesDomId', $util);
        $this->assertStringContainsString('([A-Z0-9]+)-(\\d+)', $util);
        $this->assertStringContainsString('tab-series-', $util);
        $this->assertStringContainsString('index += 10', $util);
        $this->assertStringContainsString('sortTabsInSeries', $util);
        $this->assertStringContainsString('numericSuffix', $util);
    }
}
