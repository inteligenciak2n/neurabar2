<?php

namespace Tests\Unit\Composables;

use PHPUnit\Framework\TestCase;

class UseAutosaveFormTest extends TestCase
{
    public function test_autosave_persists_when_a_field_is_left_while_editing_is_enabled(): void
    {
        $composable = file_get_contents(dirname(__DIR__, 3).'/resources/js/Composables/useAutosaveForm.js');
        $editing = file_get_contents(dirname(__DIR__, 3).'/resources/js/Composables/useSettingsEditing.js');
        $layout = file_get_contents(dirname(__DIR__, 3).'/resources/js/Layouts/SettingsLayout.vue');

        $this->assertNotFalse($composable);
        $this->assertNotFalse($editing);
        $this->assertStringNotContainsString('watchDebounced', $composable);
        $this->assertStringContainsString('useSettingsEditing', $composable);
        $this->assertStringContainsString('form.isDirty', $composable);
        $this->assertStringContainsString('form.processing', $composable);
        $this->assertStringContainsString('registerAutosave', $composable);
        $this->assertStringContainsString('wasEnabled && !enabled', $composable);
        $this->assertStringContainsString('onBeforeUnmount', $composable);
        $this->assertStringContainsString('pagehide', $layout);
        $this->assertStringContainsString('@change="commitFields"', $layout);
        $this->assertStringContainsString('@focusout="commitFields"', $layout);
        $this->assertStringContainsString('flushAutosave()', $layout);
        $this->assertStringContainsString('v-if="editingEnabled"', $layout);
        $this->assertStringContainsString("*{{ t('Automatic saving') }}", $layout);

        $this->assertStringContainsString('registerAutosave', $editing);
        $this->assertStringContainsString('flushAutosave', $editing);
        $this->assertStringContainsString('enableEditing', $editing);
    }
}
