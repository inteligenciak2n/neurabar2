<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class TabsCopyTest extends TestCase
{
    public function test_tabs_settings_page_contains_the_comandas_section(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/Tabs.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/AttendanceChannels.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($page);
        $this->assertStringContainsString('data-section="tabs"', $page);
        $this->assertStringContainsString('rounded-xl border-2 border-ocean-deep/25', $page);
        $this->assertStringContainsString("__('Tabs')", $page);
        $this->assertStringContainsString("__('Enabled')", $page);
        $this->assertStringContainsString("__('Disabled')", $page);
        $this->assertStringContainsString("'bg-success text-white shadow-sm'", $page);
        $this->assertStringContainsString("'bg-destructive text-white shadow-sm'", $page);
        $this->assertStringContainsString('settings.tabs.enabled', $page);
        $this->assertStringContainsString('grayscale opacity-50', $page);
        $this->assertStringContainsString("__('All channels that require a tab share this list.')", $page);
        $this->assertStringContainsString("__('Use a prefix before the numbers to organize internally.')", $page);
        $this->assertStringContainsString(":title=\"__('Create a new tab number')\"", $page);
        $this->assertStringContainsString(":title=\"__('Created tab numbers')\"", $page);
        $this->assertStringContainsString("__('Create a single tab')", $page);
        $this->assertStringContainsString('v-model="tabForm.notes"', $page);
        $this->assertStringContainsString("__('Create several tabs')", $page);
        $this->assertStringContainsString("__('Add tab')", $page);
        $this->assertStringContainsString('tabCodeHasNumber', $page);
        $this->assertStringContainsString("__('A tab code must include a number.')", $page);
        $this->assertStringContainsString("__('Create range')", $page);
        $this->assertStringContainsString('settings.tabs.store', $page);
        $this->assertStringContainsString("__('Prefix')", $page);
        $this->assertStringContainsString("__('e.g. A or CASA1')", $page);
        $this->assertStringContainsString("__('Use words at the start of each tab if you want to distinguish how the tabs are used.')", $page);
        $this->assertStringContainsString('min-w-[18rem] flex-[2]', $page);
        $this->assertSame(
            2,
            substr_count($page, "__('Use words at the start of each tab if you want to distinguish how the tabs are used.')"),
        );
        $this->assertStringContainsString('mt-1 text-xs text-muted-foreground', $page);
        $this->assertStringContainsString('formatPrefixLabel', $page);
        $this->assertStringContainsString("__('Observation')", $page);
        $this->assertStringContainsString("__('Each tab code can be used only once.')", $page);
        $this->assertStringContainsString("__('Tabs created successfully')", $page);
        $this->assertStringContainsString('tabSeriesDomId', $page);
        $this->assertStringContainsString("__('Delete all')", $page);
        $this->assertStringContainsString('settings.tab-series.destroy', $page);
        $this->assertStringContainsString('confirmDeleteSeries', $page);
        $this->assertStringContainsString('groupTabsForDisplay', $page);
        $this->assertStringContainsString('sm:grid-cols-10', $page);
        $this->assertStringContainsString('tabIsPendingDelete', $page);
        $this->assertStringNotContainsString("__('Are you sure you want to delete this tab?')", $page);
        $this->assertStringContainsString('nextTick(() => deleteTab())', $page);
        $this->assertStringContainsString("__(errors.tab || 'Could not delete this tab.')", $page);
        $this->assertStringContainsString("route('settings.tabs.destroy', { tab: tabToDelete.value.id })", $page);
        $this->assertStringContainsString("'line-through': tabIsPendingDelete(tab, series)", $page);
        $this->assertSame('Comandas', $translations['Tabs']);
        $this->assertSame('Ativado', $translations['Enabled']);
        $this->assertSame('Desativado', $translations['Disabled']);
        $this->assertSame('Adicionar comanda', $translations['Add tab']);
        $this->assertSame('Criar intervalo', $translations['Create range']);
        $this->assertSame('Números de Comandas já criados', $translations['Created tab numbers']);
        $this->assertSame(
            'Utilize palavras no início de cada comanda caso queira diferenciar os usos de suas comandas',
            $translations['Use words at the start of each tab if you want to distinguish how the tabs are used.'],
        );
    }
}
