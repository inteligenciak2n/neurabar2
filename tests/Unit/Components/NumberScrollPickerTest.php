<?php

namespace Tests\Unit\Components;

use PHPUnit\Framework\TestCase;

class NumberScrollPickerTest extends TestCase
{
    public function test_picker_opens_a_vertical_snap_wheel(): void
    {
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/NumberScrollPicker.vue');

        $this->assertNotFalse($component);
        $this->assertStringContainsString('snap-y snap-mandatory', $component);
        $this->assertStringContainsString('overflow-y-auto', $component);
        $this->assertStringContainsString('h-44', $component);
        $this->assertStringContainsString('h-12 text-2xl font-bold', $component);
        $this->assertStringContainsString('h-10 text-lg font-semibold', $component);
        $this->assertStringContainsString('compact', $component);
        $this->assertStringContainsString('hideTrigger', $component);
        $this->assertStringContainsString('data-picker-option', $component);
        $this->assertStringContainsString('data-picker-header', $component);
        $this->assertStringContainsString("item.kind === 'header'", $component);
        $this->assertStringContainsString("item.tone === 'new'", $component);
        $this->assertStringContainsString("item.tone === 'muted'", $component);
        $this->assertStringContainsString('scrollIntoView', $component);
        $this->assertStringContainsString('emit(\'update:open\'', $component);
    }
}
