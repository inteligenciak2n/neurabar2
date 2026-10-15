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
        $this->assertStringNotContainsString("__('New Attendance')", $page);

        $this->assertSame('Novo Pedido', $translations['New Order']);
        $this->assertSame('Nova mesa', $translations['New Table']);
        $this->assertSame('Nova comanda', $translations['New Tab']);
        $this->assertSame('Localizar pedidos', $translations['Locate orders']);
    }
}
