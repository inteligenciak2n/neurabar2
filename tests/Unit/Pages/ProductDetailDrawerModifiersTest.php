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
        $this->assertStringContainsString('data-product-drawer-scroll', $component);
        $this->assertStringContainsString('data-send-order', $component);
        $this->assertStringContainsString('flex w-full max-w-sm max-h-[90vh] flex-col overflow-hidden', $component);
        $this->assertStringContainsString('min-h-0 flex-1 overflow-y-auto overflow-x-hidden', $component);
        $this->assertStringContainsString('shrink-0 space-y-2 border-t border-border bg-white px-5 py-3', $component);
        $this->assertStringContainsString('data-change-tab', $component);
        $this->assertStringContainsString('data-cancel-item', $component);
        $this->assertStringContainsString("__('Change tab')", $component);
        $this->assertStringContainsString("__('Cancel item')", $component);
        $this->assertStringContainsString('canRemove', $component);
        $this->assertStringContainsString('canChangeTab', $component);
        $this->assertStringContainsString('data-drawer-tab-picker', $component);
        $this->assertStringContainsString('toggleTabPicker', $component);
        $this->assertStringContainsString('NumberScrollPicker', $component);
        $this->assertStringContainsString('data-initial-tab-label', $component);
        $this->assertStringContainsString('data-change-to-label', $component);
        $this->assertStringContainsString("__('Initial tab:')", $component);
        $this->assertStringContainsString("__('Change to:')", $component);
        $this->assertStringContainsString('hide-trigger', $component);
        $this->assertStringContainsString('data-delivery-location', $component);
        $this->assertStringContainsString('data-delivery-choice', $component);
        $this->assertStringContainsString('startDeliveryChoice', $component);
        $this->assertStringContainsString('chooseDeliveryLocation', $component);
        $this->assertStringContainsString("__('Deliver item where:')", $component);
        $this->assertStringContainsString("__('Deliver item where: :locations, to choose', { locations })", $component);
        $this->assertStringNotContainsString('max-h-[90vh] overflow-y-auto overflow-x-hidden', $component);
    }
}
