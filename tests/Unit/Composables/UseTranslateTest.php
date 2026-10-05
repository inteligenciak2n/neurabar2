<?php

namespace Tests\Unit\Composables;

use PHPUnit\Framework\TestCase;

class UseTranslateTest extends TestCase
{
    public function test_component_name_is_captured_during_setup_so_event_handlers_stay_translated(): void
    {
        $composable = file_get_contents(dirname(__DIR__, 3).'/resources/js/Composables/useTranslate.js');

        $this->assertNotFalse($composable);

        $setupName = strpos($composable, 'const setupComponentName =');
        $translateFn = strpos($composable, 'const __ =');
        $innerInstance = strpos($composable, 'const instance = getCurrentInstance()', $translateFn);

        $this->assertNotFalse($setupName);
        $this->assertNotFalse($translateFn);
        $this->assertLessThan($translateFn, $setupName);
        $this->assertFalse($innerInstance);
        $this->assertStringContainsString("componentName || setupComponentName || 'UnknownComponent'", $composable);
    }
}
