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
        $this->assertStringContainsString("__('Color of :name'", $page);
        $this->assertStringContainsString('flex-nowrap items-center gap-2', $page);
        $this->assertStringContainsString('form.color = color', $page);
        $this->assertStringNotContainsString('type="color"', $page);
        $this->assertStringContainsString('v-for="color in palette"', $page);
        $this->assertStringContainsString('channelSectionStyle(form.color)', $page);
        preg_match('/const CHANNEL_COLORS = \[([^\]]+)\]/s', $page, $paletteMatch);
        $this->assertNotEmpty($paletteMatch[1] ?? null);
        preg_match_all('/#[0-9A-Fa-f]{6}/', $paletteMatch[1], $paletteColors);
        $this->assertCount(8, $paletteColors[0]);
        $enum = file_get_contents(dirname(__DIR__, 3).'/app/Enums/AttendanceChannelColor.php');
        preg_match_all("/case \\w+ = '(#[0-9A-Fa-f]{6})'/", $enum, $enumColors);
        $this->assertSame($enumColors[1], $paletteColors[0]);
        $this->assertStringContainsString('channel-form-card', $page);
        $this->assertStringContainsString('v-model="form.description"', $page);
        $this->assertStringContainsString('channel.description', $page);
        $this->assertLessThan(
            strpos($page, 'v-model="form.is_trackable"'),
            strpos($page, 'v-model="form.sort_order"'),
        );
        $this->assertStringContainsString("__('Requires customer identifier')", $page);
        $this->assertStringContainsString("__('Ideal for Delivery (example).')", $page);
        $this->assertStringContainsString("__('Requires tab')", $page);
        $this->assertStringContainsString("__('Uses service location')", $page);
        $this->assertStringContainsString('form.active = true', $page);
        $this->assertStringContainsString('form.active = false', $page);
        $this->assertStringContainsString('v-model="form.uses_service_location"', $page);
        $this->assertStringContainsString('editingChannel && form.uses_service_location', $page);
        $this->assertStringContainsString("__('Add') }} {{ form.name }}", $page);
        $this->assertStringNotContainsString("__('Create the prefix')", $page);
        $this->assertStringContainsString('font-heading text-lg font-bold', $page);
        $this->assertStringContainsString("__('Create one') }} {{ form.name }}", $page);
        $this->assertStringContainsString("__('Create several') }} {{ form.name }}", $page);
        $this->assertStringContainsString('settings.service-locations.range', $page);
        $this->assertStringContainsString('channelLocations', $page);
        $this->assertStringContainsString('numeric: true', $page);
        $this->assertStringContainsString("sensitivity: 'base'", $page);
        $this->assertStringContainsString(':disabled="!!editingChannel"', $page);
        $this->assertStringContainsString("__('The channel name cannot be changed after it is created.')", $page);
        $this->assertStringContainsString("__('Inactivate location')", $page);
        $this->assertStringContainsString("__('Generate QR')", $page);
        $this->assertStringContainsString('generateLocationQr', $page);
        $this->assertStringContainsString('inactivateLocation', $page);
        $this->assertStringContainsString('settings.service-locations.qr', $page);
        $this->assertStringContainsString('settings.service-locations.qr-pdf', $page);
        $this->assertStringContainsString('settings.service-locations.qr-png', $page);
        $this->assertStringContainsString('settings.service-locations.qr-svg', $page);
        $this->assertStringContainsString('showLocationQr', $page);
        $this->assertStringContainsString("__('Download PDF format')", $page);
        $this->assertStringContainsString("__('Download PNG image format')", $page);
        $this->assertStringContainsString("__('Download vectorized version')", $page);
        $this->assertStringNotContainsString('window.open', $page);
        $this->assertStringContainsString('settings.service-locations.update', $page);
        $this->assertStringContainsString('confirmDeleteChannelLocations', $page);
        $this->assertStringContainsString('nextTick(() => deleteChannelLocations())', $page);
        $this->assertStringContainsString('settings.attendance-channels.locations.destroy', $page);
        $this->assertStringNotContainsString("__('Are you sure you want to delete all locations for this channel?')", $page);
        $this->assertStringContainsString('v-model="form.requires_tab"', $page);
        $this->assertStringContainsString('data-section="channels"', $page);
        $this->assertStringContainsString('rounded-xl border-2 border-ocean-deep/25', $page);
        $this->assertStringNotContainsString("__('Add Channel')", $page);
        $this->assertStringNotContainsString('openCreate', $page);
        $this->assertStringContainsString('moveUp(index)', $page);
        $this->assertStringContainsString('moveDown(index)', $page);
        $this->assertStringContainsString("route('settings.attendance-channels.reorder')", $page);
        $this->assertStringContainsString("__('Sort the attendance channels. This order is used by the order taker in New Order.')", $page);
        $this->assertStringContainsString("__('This makes New Order more practical.')", $page);
        $this->assertStringNotContainsString('<template #actions>', $page);
        $this->assertStringNotContainsString('data-section="tabs"', $page);
        $this->assertStringNotContainsString('settings.tabs.store', $page);
        $this->assertStringContainsString('rounded-lg border border-border p-4', $page);
        $this->assertStringContainsString('locationNumberIsValid', $page);
        $this->assertStringContainsString("__('A number is required.')", $page);
        $this->assertStringContainsString("'The \":name\" number already exists.'", $page);
        $this->assertStringContainsString("__('Not created: :names'", $page);
        $this->assertStringContainsString("__('Created: :names'", $page);
        $this->assertStringContainsString('singleLocationResult', $page);
        $this->assertStringContainsString('severalLocationResult', $page);
        $this->assertStringContainsString("__('Create range')", $page);
        $this->assertStringContainsString('toast.success', $page);
        $this->assertStringContainsString("__('Delete all')", $page);
        $this->assertStringContainsString('locationIsPendingDelete', $page);
        $this->assertStringNotContainsString("__('Are you sure you want to delete this location?')", $page);
        $this->assertStringContainsString('inactivateLocation', $page);
        $this->assertStringContainsString("'line-through': locationIsPendingDelete(location)", $page);
        $this->assertStringContainsString('bg-red-100 text-red-700', $page);
        $this->assertSame('Rastreável pelo cliente', $translations['Trackable by customer']);
        $this->assertSame('Descrição', $translations['Description']);
        $this->assertSame('Cor do :name', $translations['Color of :name']);
        $this->assertSame(
            'Ordene os canais de atendimento. Essa ordem será utilizada pelo realizador de pedidos "Novo Pedido".',
            $translations['Sort the attendance channels. This order is used by the order taker in New Order.'],
        );
        $this->assertSame(
            'Deixando mais prático o "Novo Pedido".',
            $translations['This makes New Order more practical.'],
        );
        $this->assertSame('Cor', $translations['Color']);
        $this->assertSame(
            'O cliente pode localizar o status de preparo do pedido.',
            $translations['The customer can check the preparation status of the order.'],
        );
        $this->assertSame('Exige identificador do cliente', $translations['Requires customer identifier']);
        $this->assertSame('Ideal para Delivery (exemplo)', $translations['Ideal for Delivery (example).']);
        $this->assertSame('Exige comanda', $translations['Requires tab']);
        $this->assertSame('Utilizará local de atendimento', $translations['Uses service location']);
        $this->assertSame(
            'Mostra mesas ou balcões deste canal no Novo Pedido.',
            $translations['Show tables or counters for this channel in New Order.'],
        );
        $this->assertSame('Comandas', $translations['Tabs']);
        $this->assertSame('Ativado', $translations['Enabled']);
        $this->assertSame('Desativado', $translations['Disabled']);
        $this->assertSame('Comandas estão desativadas.', $translations['Tabs are disabled.']);
        $this->assertSame('Adicionar', $translations['Add']);
        $this->assertSame('Crie o prefixo', $translations['Create the prefix']);
        $this->assertSame(
            'O nome do canal não pode ser alterado depois de criado.',
            $translations['The channel name cannot be changed after it is created.'],
        );
        $this->assertSame('Excluir local', $translations['Delete location']);
        $this->assertSame('Inativar local', $translations['Inactivate location']);
        $this->assertSame('Gerar QR', $translations['Generate QR']);
        $this->assertSame('QR Code gerado.', $translations['QR code generated.']);
        $this->assertSame('Baixar formato pdf', $translations['Download PDF format']);
        $this->assertSame('Baixar formato imagem (PNG)', $translations['Download PNG image format']);
        $this->assertSame('Baixar versão vetorizada', $translations['Download vectorized version']);
        $this->assertSame(
            'Tem certeza de que deseja apagar todos os locais deste canal?',
            $translations['Are you sure you want to delete all locations for this channel?'],
        );
        $this->assertSame(
            'Não é possível excluir: há um atendimento aberto neste local. Feche o atendimento e tente novamente.',
            $translations['Cannot delete this location while an attendance is open. Close the attendance first.'],
        );
        $this->assertSame(
            'Não é possível apagar: há atendimento aberto em um destes locais. Feche os atendimentos e tente novamente.',
            $translations['Cannot delete these locations while an attendance is open. Close the open attendances first.'],
        );
        $this->assertSame(
            'Não é possível excluir esta comanda: há um atendimento aberto. Feche o atendimento e tente novamente.',
            $translations['Cannot delete this tab while an attendance is open. Close the attendance first.'],
        );
        $this->assertSame(
            'Não é possível apagar estas comandas: há atendimento aberto em uma delas. Feche os atendimentos e tente novamente.',
            $translations['Cannot delete these tabs while an attendance is open. Close the open attendances first.'],
        );
        $this->assertSame('Criar uma', $translations['Create one']);
        $this->assertSame('Criar várias', $translations['Create several']);
        $this->assertSame('Adicionar comanda', $translations['Add tab']);
        $this->assertSame('Criar intervalo', $translations['Create range']);
        $this->assertSame('Prefixo', $translations['Prefix']);
        $this->assertSame('por exemplo, A ou CASA1', $translations['e.g. A or CASA1']);
        $this->assertSame(
            'Utilize palavras no início de cada comanda caso queira diferenciar os usos de suas comandas',
            $translations['Use words at the start of each tab if you want to distinguish how the tabs are used.'],
        );
        $this->assertSame('Observação', $translations['Observation']);
        $this->assertSame('Apagar todos', $translations['Delete all']);
        $this->assertSame(
            'Tem certeza de que deseja apagar todas as comandas deste prefixo?',
            $translations['Are you sure you want to delete all tabs in this prefix?'],
        );
        $this->assertSame('Cada código de comanda deve ser único.', $translations['Each tab code can be used only once.']);
        $this->assertSame('Comandas Criadas com Sucesso', $translations['Tabs created successfully']);
        $this->assertSame('Este código de comanda já está em uso.', $translations['This tab code is already in use.']);
        $this->assertSame('O código da comanda precisa ter um número.', $translations['A tab code must include a number.']);
        $this->assertSame('Informe um número.', $translations['A number is required.']);
        $this->assertSame('Não foi possível excluir esta comanda.', $translations['Could not delete this tab.']);
        $this->assertSame('Número de ":name" Já existente', $translations['The ":name" number already exists.']);
        $this->assertSame('Não criados: :names', $translations['Not created: :names']);
        $this->assertSame('Criados: :names', $translations['Created: :names']);
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
