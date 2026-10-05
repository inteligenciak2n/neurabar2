<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class SubscriptionHeaderTest extends TestCase
{
    public function test_subscription_title_shows_a_three_line_hint(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/Subscription/Index.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString("__('Subscription')", $page);
        $this->assertStringContainsString('flex min-w-0 items-center gap-3', $page);
        $this->assertStringContainsString("__('Save more time by subscribing to more modules')", $page);
        $this->assertStringContainsString("__('See each new feature and subscribe whenever you want')", $page);
        $this->assertStringContainsString("__('View your current subscription')", $page);
    }

    public function test_portuguese_subscription_hint_matches_the_requested_copy(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Index.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Assinatura', $translations['Subscription']);
        $this->assertSame(
            'Ganhe mais tempo assinando mais módulos',
            $translations['Save more time by subscribing to more modules'],
        );
        $this->assertSame(
            'Visualize cada funcionalidade nova e assine elas, quando quiser',
            $translations['See each new feature and subscribe whenever you want'],
        );
        $this->assertSame(
            'Visualize sua assinatura atual',
            $translations['View your current subscription'],
        );
    }
}
