<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class ProductDetailDrawerModifiersTest extends TestCase
{
    public function test_drawer_initializes_and_toggles_modifiers_on_the_first_click(): void
    {
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/ProductDetailDrawer.vue');

        $this->assertNotFalse($component);
        $this->assertStringContainsString('{ immediate: true }', $component);
        $this->assertStringContainsString('function isSingleChoice(group)', $component);
        $this->assertStringContainsString('return !group.multiple_selection;', $component);
        $this->assertStringContainsString('function toggleOption(group, optionId)', $component);
        $this->assertStringContainsString('function isOptionSelected(group, optionId)', $component);
        $this->assertStringContainsString('@click.prevent="toggleOption(group, option.id)"', $component);
        $this->assertStringContainsString('@click.stop.prevent="toggleOption(group, option.id)"', $component);
        $this->assertStringContainsString(':name="`modifier-${group.id}`"', $component);
        $this->assertStringContainsString(":type=\"isSingleChoice(group) ? 'radio' : 'checkbox'\"", $component);
        $this->assertStringContainsString(':checked="isOptionSelected(group, option.id)"', $component);
        $this->assertStringContainsString('next[group.id] = isSingleChoice(group) ? null : [];', $component);
        $this->assertStringNotContainsString('min_selections', $component);
        $this->assertStringNotContainsString('max_selections', $component);
        $this->assertStringNotContainsString('v-model="selectedModifiers[group.id]"', $component);
        $this->assertStringNotContainsString('selectedModifiers[group.id]?.includes(option.id)', $component);
    }
}
