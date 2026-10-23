<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class GeneralSettingsLayoutTest extends TestCase
{
    public function test_general_settings_save_automatically_without_a_save_button(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/General.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/General.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($page);
        $this->assertStringContainsString('useAutosaveForm(form, submit)', $page);
        $this->assertStringContainsString('preserveScroll: true', $page);
        $this->assertStringNotContainsString("__('Save Changes')", $page);
        $this->assertStringNotContainsString('AppButton', $page);
        $this->assertStringContainsString(":title=\"__('Fees and services charged to the customer')\"", $page);
        $this->assertStringContainsString(":title=\"__('Maximum number of tables')\"", $page);
        $this->assertStringNotContainsString(":title=\"__('Financial & Capacity')\"", $page);
        $this->assertStringContainsString(":title=\"__('Operational Requirements')\"", $page);
        $this->assertStringContainsString(":title=\"__('Geolocation')\"", $page);
        $this->assertStringContainsString('v-model="form.require_table"', $page);
        $this->assertStringContainsString('v-model="form.require_geolocation"', $page);
        $this->assertStringContainsString('v-model="form.latitude"', $page);
        $this->assertSame('Taxas e serviços cobrados do cliente', $translations['Fees and services charged to the customer']);
        $this->assertSame('Quantidade máxima de mesas', $translations['Maximum number of tables']);
        $this->assertStringContainsString("__('Fee charged on the products from the menu.')", $page);
        $this->assertStringContainsString("__('This information helps the waiter find the table when taking the order.')", $page);
        $this->assertSame('Taxa cobrada sobre os produtos do cardápio.', $translations['Fee charged on the products from the menu.']);
        $this->assertSame('Essa informação ajuda o garçom a localizar a mesa ao anotar o pedido.', $translations['This information helps the waiter find the table when taking the order.']);
        $this->assertSame('Taxa de couvert (R$)', $translations['Cover Charge (R$)']);
        $this->assertSame('Informe se há taxa de couvert ou entrada, fixa para todos os clientes.', $translations['Enter the establishment cover charge or entry fee if it applies equally to all customers.']);
    }
}
