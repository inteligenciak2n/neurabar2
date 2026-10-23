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
        $this->assertStringContainsString("__('New Table')", $page);
        $this->assertStringContainsString("__('New Tab')", $page);
        $this->assertStringContainsString("__('Locate orders')", $page);
        $this->assertStringContainsString("openCreate('order')", $page);
        $this->assertStringContainsString("openCreate('table')", $page);
        $this->assertStringContainsString("openCreate('tab')", $page);
        $this->assertStringContainsString('openLocator', $page);
        $this->assertStringContainsString('visibleAttendances', $page);
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
        $this->assertStringContainsString('sm:grid-cols-10', $page);
        $this->assertStringContainsString("__('Tab')", $page);
        $this->assertStringContainsString('form.tab_id', $page);
        $this->assertStringNotContainsString("__('New Attendance')", $page);

        $this->assertSame('Novo Pedido', $translations['New Order']);
        $this->assertSame('Nova mesa', $translations['New Table']);
        $this->assertSame('Nova comanda', $translations['New Tab']);
        $this->assertSame('Comanda', $translations['Tab']);
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
