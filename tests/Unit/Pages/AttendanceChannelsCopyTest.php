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
        $this->assertSame('Rastreável pelo cliente', $translations['Trackable by customer']);
        $this->assertSame('Descrição', $translations['Description']);
        $this->assertSame(
            'O cliente pode localizar o status de preparo do pedido.',
            $translations['The customer can check the preparation status of the order.'],
        );
        $this->assertSame('Exige identificador do cliente', $translations['Requires customer identifier']);
        $this->assertSame('Exige identificador', $translations['Requires Identifier']);
        $this->assertSame('por exemplo, Balcão', $translations['e.g. Counter']);
        $this->assertSame('Defina como o cliente é atendido', $translations['Choose how the customer is served']);
        $this->assertSame('Por exemplo: mesa, balcão, delivery ou retirada', $translations['Table, counter, delivery or pickup']);
        $this->assertSame('Valor (identificador)', $translations['Value (slug)']);
        $this->assertSame('por exemplo, balcão', $translations['e.g. counter']);
    }
}
