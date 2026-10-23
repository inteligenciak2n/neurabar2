<?php

namespace Tests\Unit\Layouts;

use PHPUnit\Framework\TestCase;

class SettingsLayoutTranslationsTest extends TestCase
{
    public function test_settings_layout_shows_a_back_button_instead_of_a_sidebar(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3).'/resources/js/Layouts/SettingsLayout.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/SettingsLayout.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($layout);
        $this->assertStringNotContainsString('<aside', $layout);
        $this->assertStringNotContainsString('navItems', $layout);
        $this->assertStringNotContainsString('{{ t(item.label) }}', $layout);
        $this->assertStringContainsString('v-if="$slots.header"', $layout);
        $this->assertStringContainsString('sticky top-16 z-20', $layout);
        $this->assertStringContainsString('mx-auto flex w-full max-w-5xl items-start justify-between gap-4', $layout);
        $this->assertStringContainsString('relative mx-auto w-full max-w-5xl', $layout);
        $this->assertStringContainsString("{{ t('Enable editing') }}", $layout);
        $this->assertStringContainsString("*{{ t('Automatic saving') }}", $layout);
        $this->assertStringContainsString('v-if="editingEnabled"', $layout);
        $this->assertStringContainsString("t('Enable editing to change this information')", $layout);
        $this->assertStringContainsString('!bg-warm-gold', $layout);
        $this->assertStringContainsString("'--normal-bg': '#a28665'", $layout);
        $this->assertStringContainsString('italic', $layout);
        $this->assertStringContainsString('role="switch"', $layout);
        $this->assertStringContainsString('@click="toggleEditing"', $layout);
        $this->assertStringContainsString('@click.capture="onLockedInteract"', $layout);
        $this->assertStringContainsString("event.target.closest('[data-enable-editing]')", $layout);
        $this->assertStringContainsString('editingEnabled.value = true', $layout);
        $this->assertStringContainsString('cursor-none select-none opacity-75', $layout);
        $this->assertStringContainsString('[&_*]:!cursor-none', $layout);
        $this->assertStringContainsString("t('Unlock editing')", $layout);
        $this->assertStringContainsString('onLockedPointerMove', $layout);
        $this->assertStringContainsString('overEdit', $layout);
        $this->assertStringContainsString("lockHint.overEdit ? 'mt-4' : 'mt-1'", $layout);
        $this->assertStringContainsString('whitespace-nowrap', $layout);
        $this->assertStringContainsString('v-if="lockHint.overEdit"', $layout);
        $this->assertStringContainsString('M22 14a8 8 0 0 1-8 8', $layout);
        $this->assertStringNotContainsString(':disabled="props.lockable && !editingEnabled"', $layout);
        $this->assertStringNotContainsString("{{ t('Cancel editing') }}", $layout);
        $this->assertStringContainsString("route().current('settings.index')", $layout);
        $this->assertStringContainsString("'settings.index'", $layout);
        $this->assertStringContainsString("{{ t('Back') }}", $layout);
        $this->assertStringContainsString('v-if="showBack"', $layout);
        $this->assertStringContainsString('@click="goBack"', $layout);
        $this->assertStringContainsString("emit('close-nested')", $layout);
        $this->assertStringContainsString('parentRoute', $layout);
        $this->assertSame('Habilitar edição', $translations['Enable editing']);
        $this->assertSame('Salvamento automático', $translations['Automatic saving']);
        $this->assertSame('Voltar', $translations['Back']);
        $this->assertSame(
            'Habilite a edição para alterar as informações',
            $translations['Enable editing to change this information'],
        );
        $this->assertSame('Desbloqueie a edição', $translations['Unlock editing']);

        foreach ([
            'Subscription' => 'Assinatura',
            'Venue Info' => 'Informações do estabelecimento',
            'General' => 'Modo de trabalho do estabelecimento',
            'Kitchen Stations' => 'Setor de preparo',
            'Preparation Statuses' => 'Status de preparação',
            'Service Locations' => 'Locais de atendimento',
            'Attendance Channels' => 'Canais de atendimento',
            'Users' => 'Usuários',
        ] as $key => $portuguese) {
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
