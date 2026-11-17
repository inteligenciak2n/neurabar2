<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class KdsWaiterCallerColumnTest extends TestCase
{
    public function test_kds_renders_a_dedicated_waiter_caller_column(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Kitchen/Kds.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString("const WAITER_CALLER_CODE = 'waiter_caller'", $page);
        $this->assertStringContainsString('waiterCalls: Array', $page);
        $this->assertStringContainsString("__('Waiter caller (text message)')", $page);
        $this->assertStringContainsString("__('No waiter calls')", $page);
        $this->assertStringContainsString("route('service-requests.acknowledge', request.id)", $page);
        $this->assertStringContainsString("route('service-requests.resolve', request.id)", $page);
        $this->assertStringContainsString('waiterCalls', $page);
        $this->assertStringContainsString('venue.${venueId.value}.service-requests', $page);
    }

    public function test_portuguese_kds_copy_names_the_waiter_caller_station(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Kds.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Chamador de Garçom (mensagem texto)', $translations['Waiter caller (text message)']);
        $this->assertSame('Sem chamados pendentes', $translations['No waiter calls']);
        $this->assertSame('Assumir', $translations['Acknowledge']);
        $this->assertSame('Resolvido', $translations['Resolve']);
        $this->assertSame('Chamados de garçom', $translations['Waiter calls']);
    }

    public function test_kitchen_stations_settings_lock_the_waiter_caller(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/KitchenStations.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('v-if="!station.code"', $page);
        $this->assertStringContainsString(':disabled="Boolean(editingStation?.code)"', $page);
        $this->assertStringContainsString("__('Always active')", $page);
        $this->assertStringContainsString("__('This station is always active')", $page);
    }
}
