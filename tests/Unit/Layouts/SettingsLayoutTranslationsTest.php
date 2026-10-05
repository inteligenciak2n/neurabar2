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
        $this->assertStringContainsString('sticky top-16 z-20', $layout);
        $this->assertStringContainsString('absolute inset-0 z-10 cursor-not-allowed', $layout);
        $this->assertStringContainsString('min-w-0 flex-1', $layout);
        $this->assertStringNotContainsString('<template #header>', $layout);
        $this->assertStringContainsString("{{ t('Enable editing') }}", $layout);
        $this->assertStringContainsString("{{ t('Automatic saving') }}", $layout);
        $this->assertStringContainsString("t('Enable editing to change this information')", $layout);
        $this->assertStringContainsString('italic', $layout);
        $this->assertStringContainsString('role="switch"', $layout);
        $this->assertStringContainsString('@click="toggleEditing"', $layout);
        $this->assertStringContainsString('@click="showLockedMessage"', $layout);
        $this->assertStringContainsString(':disabled="props.lockable && !editingEnabled"', $layout);
        $this->assertStringNotContainsString("{{ t('Cancel editing') }}", $layout);
        $this->assertSame('Habilitar edição', $translations['Enable editing']);
        $this->assertSame('salvamento automático', $translations['Automatic saving']);
        $this->assertSame(
            'Habilite a edição para alterar as informações',
            $translations['Enable editing to change this information'],
        );

        foreach ([
            'Subscription' => 'Assinatura',
            'Venue Info' => 'Informações do estabelecimento',
            'General' => 'Geral',
            'Kitchen Stations' => 'Setor de preparo',
            'Preparation Statuses' => 'Status de preparação',
            'Service Locations' => 'Locais de serviço',
            'Attendance Channels' => 'Canais de atendimento',
            'Users' => 'Usuários',
        ] as $key => $portuguese) {
            $this->assertStringContainsString("label: '{$key}'", $layout);
            $this->assertSame($portuguese, $translations[$key]);
        }

        $stations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/KitchenStations.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Setor de preparo', $stations['Kitchen Stations']);
        $this->assertSame('Adicionar setor', $stations['Add Station']);
        $this->assertSame('Excluir setor de preparo', $stations['Delete Kitchen Station']);
        $this->assertSame('Editar setor', $stations['Edit Station']);
        $this->assertSame('Novo setor', $stations['New Station']);
    }
}
