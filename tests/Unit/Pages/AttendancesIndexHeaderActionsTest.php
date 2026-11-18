<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class AttendancesIndexHeaderActionsTest extends TestCase
{
    public function test_attendances_header_has_order_table_tab_and_locate_actions(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Attendances/Index.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Index.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($page);
        $this->assertStringContainsString("__('New Order')", $page);
        $this->assertStringContainsString("__('Locate orders')", $page);
        $this->assertStringContainsString("route('attendances.create')", $page);
        $this->assertStringNotContainsString('updateServiceLocation', $page);
        $this->assertStringNotContainsString('@click="openCreate(\'order\')"', $page);
        $this->assertStringNotContainsString('@click="openCreate(\'table\')"', $page);
        $this->assertStringNotContainsString('@click="openCreate(\'tab\')"', $page);
        $this->assertStringNotContainsString('data-start-order', $page);
        $this->assertStringContainsString('openLocator', $page);
        $this->assertStringContainsString('visibleAttendances', $page);
        $this->assertStringContainsString('visibleAttendanceGroups', $page);
        $this->assertStringContainsString('data-initiated-by', $page);
        $this->assertStringContainsString('text-[10px] leading-tight text-muted-foreground', $page);
        $this->assertStringContainsString('flex shrink-0 flex-col items-end', $page);
        $this->assertStringContainsString("<AppButton size=\"sm\">{{ __('New Order') }}</AppButton>", $page);
        $this->assertStringNotContainsString("<AppButton size=\"sm\">{{ __('Take Order') }}</AppButton>", $page);
        $this->assertStringContainsString('attendanceTableLabel', $page);
        $this->assertStringContainsString('attendanceTabCodes', $page);
        $this->assertStringContainsString('data-board-table', $page);
        $this->assertStringContainsString('data-board-tabs', $page);
        $this->assertLessThan(
            strpos($page, 'data-board-tabs'),
            strpos($page, 'data-board-table'),
        );
        $this->assertStringContainsString("__('Tabs:')", $page);
        $this->assertStringNotContainsString('attendance.attendance_channel?.name', $page);
        $this->assertStringNotContainsString("__('Identifier:')", $page);
        $this->assertStringContainsString("__('Items to search')", $page);
        $this->assertStringContainsString('locatorFields', $page);
        $this->assertStringContainsString('text-xl font-bold', $page);
        $this->assertStringContainsString('tables: true', $page);
        $this->assertStringContainsString('tab: true', $page);
        $this->assertStringContainsString('order: true', $page);
        $this->assertStringContainsString('name: true', $page);
        $this->assertStringContainsString('product: true', $page);
        $this->assertStringNotContainsString('tables: false', $page);
        $this->assertStringContainsString('attendanceMatchesLocator', $page);
        $this->assertStringContainsString("{ key: 'tables', label: 'Tables' }", $page);
        $this->assertStringContainsString("{ key: 'tab', label: 'Tab' }", $page);
        $this->assertStringContainsString("{ key: 'order', label: 'Order' }", $page);
        $this->assertStringContainsString("{ key: 'name', label: 'Name' }", $page);
        $this->assertStringContainsString("{ key: 'product', label: 'Product' }", $page);
        $this->assertStringContainsString('availableTabs', $page);
        $this->assertStringContainsString('props.tabs', $page);
        $this->assertStringContainsString('availableTabGroups', $page);
        $this->assertStringContainsString('groupTabsByPrefix', $page);
        $this->assertStringContainsString("__('No free tabs.')", $page);
        $this->assertStringNotContainsString('No free tabs for this channel.', $page);
        $this->assertStringContainsString('max-w-md', $page);
        $this->assertStringContainsString('channelChipStyle', $page);
        $this->assertStringContainsString('role="tablist"', $page);
        $this->assertStringContainsString('selectTab(tab)', $page);
        $this->assertStringContainsString('locationMatchingTab', $page);
        $this->assertStringContainsString('uses_service_location', $page);
        $this->assertStringContainsString('pendingLocationConfirmation', $page);
        $this->assertStringContainsString('NumberScrollPicker', $page);
        $this->assertStringContainsString('chooseLocation', $page);
        $this->assertStringContainsString('openPicker', $page);
        $this->assertStringContainsString("__('Choose a tab number')", $page);
        $this->assertStringContainsString("__('Choose a table number')", $page);
        $this->assertStringContainsString('max-h-[55vh]', $page);
        $this->assertStringContainsString('touch-manipulation', $page);
        $this->assertStringContainsString("__('Tap the table number to confirm. The order is not locked.')", $page);
        $this->assertStringContainsString('canGoToMenu', $page);
        $this->assertStringNotContainsString('needsTab.value && !form.service_location_id', $page);
        $this->assertStringContainsString("__('Go to the menu')", $page);
        $this->assertStringContainsString("__('More options')", $page);
        $this->assertStringContainsString("__('Tab')", $page);
        $this->assertStringContainsString('form.tab_id', $page);
        $this->assertStringContainsString("__('New Attendance')", $page);
        $this->assertStringContainsString("__('Open Attendances')", $page);
        $this->assertStringContainsString('data-open-channel-tabs', $page);
        $this->assertStringContainsString('data-open-channel-tab', $page);
        $this->assertStringContainsString('boardChannelId', $page);
        $this->assertStringContainsString('boardChannels', $page);
        $this->assertStringContainsString('channelOpenCount', $page);
        $this->assertStringContainsString('selectBoardChannel', $page);
        $this->assertStringContainsString('{{ channel.name }} ({{ channelOpenCount(channel.id) }})', $page);
        $this->assertStringContainsString('startWithForm', $page);
        $this->assertStringContainsString('closeForm', $page);
        $this->assertSame('Novo Atendimento', $translations['New Attendance']);
        $this->assertSame('Atendimentos Abertos', $translations['Open Attendances']);

        $this->assertSame('Ir para o cardápio', $translations['Go to the menu']);
        $this->assertSame('Escolha o número da comanda', $translations['Choose a tab number']);
        $this->assertSame('Escolha o número da mesa', $translations['Choose a table number']);
        $this->assertSame(
            'Toque no número da mesa para confirmar. O pedido não fica travado.',
            $translations['Tap the table number to confirm. The order is not locked.'],
        );
        $this->assertSame('Mais opções', $translations['More options']);
        $this->assertSame('Novo Pedido', $translations['New Order']);
        $this->assertSame('Nova mesa', $translations['New Table']);
        $this->assertSame('Nova comanda', $translations['New Tab']);
        $this->assertSame('Cadastrar comanda', $translations['Register tab']);
        $this->assertStringContainsString('data-register-tab', $page);
        $this->assertStringContainsString('canRegisterTab', $page);
        $this->assertStringContainsString('registerTabOnGroup', $page);
        $this->assertStringContainsString('stay: true', $page);
        $this->assertSame('Comanda', $translations['Tab']);
        $this->assertSame('Comanda:', $translations['Tab:']);
        $this->assertSame('Comandas:', $translations['Tabs:']);
        $this->assertSame('Não há comandas livres.', $translations['No free tabs.']);
        $this->assertSame('Localizar pedidos', $translations['Locate orders']);
        $this->assertSame('Itens para pesquisar', $translations['Items to search']);
        $this->assertSame('Mesas', $translations['Tables']);
        $this->assertSame('Comanda', $translations['Tab']);
        $this->assertSame('Pedido', $translations['Order']);
        $this->assertSame('Nome', $translations['Name']);
        $this->assertSame('Produto', $translations['Product']);
    }
}
