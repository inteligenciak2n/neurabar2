<?php

namespace Tests\Unit\Layouts;

use PHPUnit\Framework\TestCase;

class SettingsLayoutTranslationsTest extends TestCase
{
    public function test_settings_sidebar_labels_are_translated_in_portuguese(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3).'/resources/js/Layouts/SettingsLayout.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/SettingsLayout.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($layout);
        $this->assertStringContainsString("{{ t('Settings') }}", $layout);
        $this->assertStringContainsString('{{ t(item.label) }}', $layout);
        $this->assertSame('Configurações', $translations['Settings']);
        $this->assertStringContainsString('v-if="$slots.header"', $layout);
        $this->assertStringContainsString('min-w-0 flex-1', $layout);
        $this->assertStringNotContainsString('<template #header>', $layout);

        foreach ([
            'Subscription' => 'Assinatura',
            'Venue Info' => 'Informações do estabelecimento',
            'General' => 'Geral',
            'Kitchen Stations' => 'Estações de cozinha',
            'Preparation Statuses' => 'Status de preparação',
            'Service Locations' => 'Locais de serviço',
            'Attendance Channels' => 'Canais de atendimento',
            'Users' => 'Usuários',
        ] as $key => $portuguese) {
            $this->assertStringContainsString("label: '{$key}'", $layout);
            $this->assertSame($portuguese, $translations[$key]);
        }
    }
}
