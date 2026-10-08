<?php

namespace Tests\Unit\Composables;

use PHPUnit\Framework\TestCase;

class UseAutosaveFormTest extends TestCase
{
    public function test_autosave_persists_dirty_forms_while_editing_is_enabled(): void
    {
        $composable = file_get_contents(dirname(__DIR__, 3).'/resources/js/Composables/useAutosaveForm.js');
        $editing = file_get_contents(dirname(__DIR__, 3).'/resources/js/Composables/useSettingsEditing.js');
        $layout = file_get_contents(dirname(__DIR__, 3).'/resources/js/Layouts/SettingsLayout.vue');

        $this->assertNotFalse($composable);
        $this->assertNotFalse($editing);
        $this->assertStringContainsString("import { watchDebounced } from '@vueuse/core'", $composable);
        $this->assertStringContainsString('useSettingsEditing', $composable);
        $this->assertStringContainsString('form.isDirty', $composable);
        $this->assertStringContainsString('form.processing', $composable);
        $this->assertStringContainsString('maxWait: 2000', $composable);
        $this->assertStringContainsString('wasEnabled && !enabled', $composable);
        $this->assertStringContainsString('onBeforeUnmount', $composable);
        $this->assertStringNotContainsString('inject(', $composable);

        $this->assertStringContainsString('useSettingsEditing', $layout);
        $this->assertStringContainsString('editingEnabled.value = !editingEnabled.value', $layout);
        $this->assertStringContainsString('enableEditing', $editing);
        $this->assertStringNotContainsString('router.visit', $layout);
        $this->assertStringNotContainsString("provide('settingsEditingEnabled'", $layout);
    }
}
