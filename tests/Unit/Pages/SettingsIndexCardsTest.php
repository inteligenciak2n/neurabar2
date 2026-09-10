<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class SettingsIndexCardsTest extends TestCase
{
    public function test_settings_cards_stack_vertically_with_section_cards(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/Index.vue');
        $card = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/SettingsSectionCard.vue');

        $this->assertNotFalse($page);
        $this->assertNotFalse($card);
        $this->assertStringContainsString('flex max-w-5xl flex-col gap-2', $page);
        $this->assertStringContainsString('SettingsSectionCard', $page);
        $this->assertStringContainsString('min-h-[7.5rem]', $card);
        $this->assertStringContainsString('w-48 shrink-0', $card);
        $this->assertStringContainsString('overflow-hidden', $card);
        $this->assertStringNotContainsString('sm:grid-cols-2', $page);
        $this->assertStringContainsString(
            'Choose a section to manage subscription, venue, users, locations and how the operation works.',
            $page,
        );
        $this->assertStringContainsString(
            'Configure the system to best serve your establishment.',
            $page,
        );
    }

    public function test_portuguese_settings_index_explains_what_can_be_configured(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Index.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Configurações', $translations['Settings']);
        $this->assertSame(
            'Escolha uma seção para gerenciar assinatura, estabelecimento, usuários, locais e o funcionamento da operação.',
            $translations['Choose a section to manage subscription, venue, users, locations and how the operation works.'],
        );
        $this->assertSame(
            'Configure o sistema para atender o seu estabelecimento da melhor forma.',
            $translations['Configure the system to best serve your establishment.'],
        );
    }
}
