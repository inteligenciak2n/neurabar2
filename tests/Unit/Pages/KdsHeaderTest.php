<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class KdsHeaderTest extends TestCase
{
    public function test_kds_header_is_compact_with_live_badge_and_station_chips(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Kitchen/Kds.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString("__('Kitchen KDS')", $page);
        $this->assertStringContainsString("__('Live')", $page);
        $this->assertStringContainsString("__('Show')", $page);
        $this->assertStringContainsString('data-station-chip', $page);
        $this->assertStringContainsString("__('Oldest first')", $page);
        $this->assertStringContainsString("__('up to 10 min')", $page);
        $this->assertStringContainsString("__('10 to 20 min')", $page);
        $this->assertStringContainsString("__('over 20 min')", $page);
        $this->assertStringContainsString("__('more than 1 unit')", $page);
        $this->assertStringContainsString('data-qty-highlight', $page);
        $this->assertStringContainsString('data-open-separate-screen', $page);
        $this->assertStringContainsString("__('Open separate screen')", $page);
        $this->assertStringContainsString('bg-emerald-600', $page);
        $this->assertStringContainsString('data-fullscreen', $page);
        $this->assertStringContainsString("__('Full screen')", $page);
        $this->assertStringContainsString('data-kds-card', $page);
        $this->assertStringContainsString('data-kds-table', $page);
        $this->assertStringContainsString("__('Start prep')", $page);
        $this->assertStringNotContainsString('shrink-0 font-heading text-4xl font-bold', $page);
        $this->assertStringNotContainsString('py-6 px-4 sm:px-6', $page);
    }

    public function test_portuguese_kds_header_matches_the_compact_copy(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Kds.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Setor de preparo', $translations['Kitchen KDS']);
        $this->assertSame('Ao vivo', $translations['Live']);
        $this->assertSame('Mostrar', $translations['Show']);
        $this->assertSame('Garçom', $translations['Waiter']);
        $this->assertSame('Chamados de garçom', $translations['Waiter calls']);
        $this->assertSame('Tela cheia', $translations['Full screen']);
        $this->assertSame('até 10 min', $translations['up to 10 min']);
        $this->assertSame('10 a 20 min', $translations['10 to 20 min']);
        $this->assertSame('mais de 20 min', $translations['over 20 min']);
        $this->assertSame('mais de 1 unidade', $translations['more than 1 unit']);
        $this->assertSame('Mais antigos primeiro', $translations['Oldest first']);
        $this->assertSame('Iniciar preparo', $translations['Start prep']);
        $this->assertSame('Abrir tela separada', $translations['Open separate screen']);
        $this->assertSame('Assumir', $translations['Acknowledge']);
        $this->assertSame('Resolvido', $translations['Resolve']);
    }
}
