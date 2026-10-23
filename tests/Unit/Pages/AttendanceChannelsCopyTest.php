<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class AttendanceChannelsCopyTest extends TestCase
{
    public function test_portuguese_channel_flags_are_translated(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/AttendanceChannels.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/AttendanceChannels.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($page);
        $this->assertStringContainsString("<SettingsSectionHeader :title=\"__('Attendance Channels')\">", $page);
        $this->assertStringContainsString("__('Choose how the customer is served')", $page);
        $this->assertStringContainsString("__('Table, counter, delivery or pickup')", $page);
        $this->assertStringContainsString("__('Trackable by customer')", $page);
        $this->assertStringContainsString("__('The customer can check the preparation status of the order.')", $page);
        $this->assertStringContainsString("__('Description')", $page);
        $this->assertStringContainsString('v-model="form.description"', $page);
        $this->assertStringContainsString('channel.description', $page);
        $this->assertLessThan(
            strpos($page, 'v-model="form.is_trackable"'),
            strpos($page, 'v-model="form.sort_order"'),
        );
        $this->assertStringContainsString("__('Requires customer identifier')", $page);
        $this->assertStringContainsString("__('Requires tab')", $page);
        $this->assertStringContainsString("__('Add') }} {{ form.name }}", $page);
        $this->assertStringNotContainsString("__('Create the prefix')", $page);
        $this->assertStringContainsString('font-heading text-lg font-bold', $page);
        $this->assertStringContainsString("__('Create one')", $page);
        $this->assertStringContainsString("__('Create several')", $page);
        $this->assertStringContainsString('settings.service-locations.range', $page);
        $this->assertStringContainsString('channelLocations', $page);
        $this->assertStringContainsString(':disabled="!!editingChannel"', $page);
        $this->assertStringContainsString("__('The channel name cannot be changed after it is created.')", $page);
        $this->assertStringContainsString("__('Delete location')", $page);
        $this->assertStringContainsString('confirmDeleteLocation', $page);
        $this->assertStringContainsString('settings.service-locations.destroy', $page);
        $this->assertStringContainsString('confirmDeleteChannelLocations', $page);
        $this->assertStringContainsString('settings.attendance-channels.locations.destroy', $page);
        $this->assertStringContainsString('v-model="form.requires_tab"', $page);
        $this->assertStringContainsString("__('Tabs')", $page);
        $this->assertStringContainsString('font-heading text-2xl font-bold', $page);
        $this->assertStringContainsString("__('All channels that require a tab share this list.')", $page);
        $this->assertStringContainsString("__('Use a prefix before the numbers to organize internally.')", $page);
        $this->assertStringContainsString(":title=\"__('Create a new tab number')\"", $page);
        $this->assertStringContainsString(":title=\"__('Created tab numbers')\"", $page);
        $this->assertStringContainsString("__('Create a single tab')", $page);
        $this->assertStringContainsString('v-model="tabForm.notes"', $page);
        $this->assertStringContainsString("__('Create several tabs')", $page);
        $this->assertStringContainsString('rounded-lg border border-border p-4', $page);
        $this->assertStringContainsString("__('Add tab')", $page);
        $this->assertStringContainsString("__('Create range')", $page);
        $this->assertStringContainsString('settings.tabs.store', $page);
        $this->assertStringContainsString('anyChannelUsesTabs', $page);
        $this->assertStringContainsString("__('Prefix')", $page);
        $this->assertStringContainsString("__('e.g. A or CASA1')", $page);
        $this->assertStringContainsString('formatPrefixLabel', $page);
        $this->assertStringContainsString('w-40', $page);
        $this->assertStringContainsString("__('Observation')", $page);
        $this->assertStringContainsString("__('Each tab code can be used only once.')", $page);
        $this->assertStringContainsString("__('Tabs created successfully')", $page);
        $this->assertStringContainsString('toast.success', $page);
        $this->assertStringContainsString('scrollIntoView', $page);
        $this->assertStringContainsString('tabSeriesDomId', $page);
        $this->assertStringContainsString("__('Delete all')", $page);
        $this->assertStringContainsString('settings.tab-series.destroy', $page);
        $this->assertStringContainsString('confirmDeleteSeries', $page);
        $this->assertStringContainsString('groupTabsForDisplay', $page);
        $this->assertStringContainsString('sm:grid-cols-10', $page);
        $this->assertSame('Rastreável pelo cliente', $translations['Trackable by customer']);
        $this->assertSame('Descrição', $translations['Description']);
        $this->assertSame(
            'O cliente pode localizar o status de preparo do pedido.',
            $translations['The customer can check the preparation status of the order.'],
        );
        $this->assertSame('Exige identificador do cliente', $translations['Requires customer identifier']);
        $this->assertSame('Exige comanda', $translations['Requires tab']);
        $this->assertSame('Comandas', $translations['Tabs']);
        $this->assertSame('Adicionar', $translations['Add']);
        $this->assertSame('Crie o prefixo', $translations['Create the prefix']);
        $this->assertSame(
            'O nome do canal não pode ser alterado depois de criado.',
            $translations['The channel name cannot be changed after it is created.'],
        );
        $this->assertSame('Excluir local', $translations['Delete location']);
        $this->assertSame(
            'Tem certeza de que deseja apagar todos os locais deste canal?',
            $translations['Are you sure you want to delete all locations for this channel?'],
        );
        $this->assertSame('Criar uma', $translations['Create one']);
        $this->assertSame('Criar várias', $translations['Create several']);
        $this->assertSame('Adicionar comanda', $translations['Add tab']);
        $this->assertSame('Criar intervalo', $translations['Create range']);
        $this->assertSame('Prefixo', $translations['Prefix']);
        $this->assertSame('por exemplo, A ou CASA1', $translations['e.g. A or CASA1']);
        $this->assertSame('Observação', $translations['Observation']);
        $this->assertSame('Apagar todos', $translations['Delete all']);
        $this->assertSame(
            'Tem certeza de que deseja apagar todas as comandas deste prefixo?',
            $translations['Are you sure you want to delete all tabs in this prefix?'],
        );
        $this->assertSame('Cada código de comanda deve ser único.', $translations['Each tab code can be used only once.']);
        $this->assertSame('Comandas Criadas com Sucesso', $translations['Tabs created successfully']);
        $this->assertSame('Este código de comanda já está em uso.', $translations['This tab code is already in use.']);
        $this->assertSame('Todos os códigos deste intervalo já existem.', $translations['Every code in this range already exists.']);
        $this->assertSame('Números', $translations['Numbers']);
        $this->assertSame(
            'Todos os canais que exigem comanda compartilham esta lista.',
            $translations['All channels that require a tab share this list.'],
        );
        $this->assertSame(
            'Use um prefixo antes dos números para organizar internamente.',
            $translations['Use a prefix before the numbers to organize internally.'],
        );
        $this->assertSame('Criar um novo número de comanda', $translations['Create a new tab number']);
        $this->assertSame('Criar uma única comanda', $translations['Create a single tab']);
        $this->assertSame('Criar várias comandas', $translations['Create several tabs']);
        $this->assertSame('Números de Comandas já criados', $translations['Created tab numbers']);
        $this->assertSame('Exige identificador', $translations['Requires Identifier']);
        $this->assertSame('por exemplo, Balcão', $translations['e.g. Counter']);
        $this->assertSame('Defina como o cliente é atendido', $translations['Choose how the customer is served']);
        $this->assertSame('Por exemplo: mesa, balcão, delivery ou retirada', $translations['Table, counter, delivery or pickup']);
        $this->assertSame('Valor (identificador)', $translations['Value (slug)']);
        $this->assertSame('por exemplo, balcão', $translations['e.g. counter']);
    }
}
