<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class AttendancesShowPageTest extends TestCase
{
    public function test_show_page_follows_the_table_overview_layout(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Attendances/Show.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Show.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($page);
        $this->assertStringContainsString('data-show-table', $page);
        $this->assertStringContainsString('data-show-tabs', $page);
        $this->assertStringContainsString('data-show-tab', $page);
        $this->assertStringContainsString('data-pending-summary', $page);
        $this->assertStringContainsString('data-category-summary', $page);
        $this->assertStringContainsString('data-pending-items', $page);
        $this->assertStringContainsString('data-waiting-duration', $page);
        $this->assertStringContainsString('data-waiting-time', $page);
        $this->assertStringContainsString('bg-red-600', $page);
        $this->assertStringContainsString('pendingWaitDuration', $page);
        $this->assertStringContainsString('data-item-tab', $page);
        $this->assertStringContainsString('data-tab-card', $page);
        $this->assertStringContainsString('data-table-time', $page);
        $this->assertStringContainsString('data-table-duration', $page);
        $this->assertStringContainsString('data-register-tab', $page);
        $this->assertStringContainsString('tableAttendances', $page);
        $this->assertStringContainsString('itemIsServed', $page);
        $this->assertStringContainsString('formatDurationHm', $page);
        $this->assertStringContainsString('padStart(2, \'0\')', $page);
        $this->assertStringContainsString('openedAt', $page);
        $this->assertStringContainsString('lastOrderAt', $page);
        $this->assertStringContainsString("__('Table time')", $page);
        $this->assertStringContainsString("__('Table total')", $page);
        $this->assertStringContainsString("__('Waiting for delivery')", $page);
        $this->assertStringContainsString("__('By category')", $page);
        $this->assertStringContainsString("__('Last order :minutes min ago'", $page);
        $this->assertStringContainsString("__('Pending')", $page);
        $this->assertStringContainsString("__('Served')", $page);
        $this->assertStringContainsString("__('Register tab')", $page);
        $this->assertStringContainsString('stay: true', $page);
        $this->assertSame('Tempo de mesa', $translations['Table time']);
        $this->assertSame('Total da mesa', $translations['Table total']);
        $this->assertSame('Aguardando entrega', $translations['Waiting for delivery']);
        $this->assertSame('Último pedido há :minutes min', $translations['Last order :minutes min ago']);
        $this->assertSame('Por categoria', $translations['By category']);
        $this->assertSame('Pendentes', $translations['Pending']);
        $this->assertSame('Atendidos', $translations['Served']);
        $this->assertSame('Aberta', $translations['Open table']);
        $this->assertSame('Cadastrar comanda', $translations['Register tab']);
        $this->assertSame('Comanda', $translations['Tab']);
    }
}
