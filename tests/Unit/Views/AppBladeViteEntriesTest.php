<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class AppBladeViteEntriesTest extends TestCase
{
    public function test_root_view_loads_the_app_bundle_without_a_page_entry(): void
    {
        $view = file_get_contents(dirname(__DIR__, 3).'/resources/views/app.blade.php');

        $this->assertNotFalse($view);
        $this->assertStringContainsString("@vite(['resources/js/app.js'])", $view);
        $this->assertStringNotContainsString('Pages/{$page', $view);
    }
}
