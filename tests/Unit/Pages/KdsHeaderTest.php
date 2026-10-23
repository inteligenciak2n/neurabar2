<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class KdsHeaderTest extends TestCase
{
    public function test_kds_title_shows_italic_subtitle_and_two_line_hint(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Kitchen/Kds.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString("__('Kitchen KDS')", $page);
        $this->assertStringContainsString('shrink-0 font-heading text-4xl font-bold', $page);
        $this->assertStringContainsString('text-sm italic leading-tight', $page);
        $this->assertStringContainsString("__('kitchen display system')", $page);
        $this->assertStringContainsString('flex min-w-0 items-center gap-3', $page);
        $this->assertStringContainsString("__('Real-time order viewer')", $page);
        $this->assertStringContainsString("__('Select which monitors you want to view in each monitor environment')", $page);
        $this->assertStringContainsString('mt-3 block italic', $page);
        $this->assertStringContainsString("__('* add, change Prep Stations in Configure the System')", $page);
        $this->assertStringContainsString("<AppButton size=\"sm\" @click=\"reload\">{{ __('Refresh') }}</AppButton>", $page);
        $this->assertStringContainsString('sticky top-44 z-10', $page);
        $this->assertStringNotContainsString('variant="ghost"', $page);
        $this->assertStringNotContainsString('py-6 px-4 sm:px-6', $page);
    }

    public function test_portuguese_kds_header_matches_the_requested_copy(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Kds.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Setor de preparo', $translations['Kitchen KDS']);
        $this->assertSame('kitchen display system', $translations['kitchen display system']);
        $this->assertSame('Visualizador de pedidos em tempo real', $translations['Real-time order viewer']);
        $this->assertSame(
            'Selecione quais monitores deseja visualizar no ambiente de cada monitor',
            $translations['Select which monitors you want to view in each monitor environment'],
        );
        $this->assertSame(
            '* adicione, altere Setores de Preparo em Configurar o Sistema',
            $translations['* add, change Prep Stations in Configure the System'],
        );
        $this->assertSame('Atualizar', $translations['Refresh']);
    }
}
