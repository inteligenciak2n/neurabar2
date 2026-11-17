<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class DeliveryAddressFieldsTest extends TestCase
{
    public function test_delivery_address_form_has_the_expected_fields_and_via_cep_lookup(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Orders/DeliveryAddressFields.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('data-delivery-address', $page);
        $this->assertStringContainsString('data-delivery-zip', $page);
        $this->assertStringContainsString('data-delivery-street', $page);
        $this->assertStringContainsString('data-delivery-number', $page);
        $this->assertStringContainsString('data-no-number', $page);
        $this->assertStringContainsString('data-delivery-complement', $page);
        $this->assertStringContainsString('data-delivery-neighborhood', $page);
        $this->assertStringContainsString('data-delivery-fee', $page);
        $this->assertStringContainsString('data-register-neighborhood', $page);
        $this->assertStringContainsString('data-open-in-maps', $page);
        $this->assertStringContainsString('data.bairro', $page);
        $this->assertStringContainsString('deliveryMapsUrl', $page);
        $this->assertStringContainsString('attendances.neighborhoods.store', $page);
        $this->assertStringContainsString('data-delivery-reference', $page);
        $this->assertStringContainsString('data-delivery-phone', $page);
        $this->assertStringContainsString('https://viacep.com.br/ws/', $page);
        $this->assertStringContainsString("__('ZIP code not found, fill in manually')", $page);
        $this->assertStringContainsString("__('Neighborhood not served or not registered')", $page);
        $this->assertStringContainsString("__('Use a new address')", $page);
        $this->assertStringContainsString("__('No number')", $page);
        $this->assertStringContainsString('S/N', $page);
        $this->assertStringContainsString('numberInput.value?.focus()', $page);
        $this->assertStringContainsString('inputmode="numeric"', $page);
    }

    public function test_portuguese_labels_are_translated(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/DeliveryAddressFields.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('CEP', $translations['ZIP code']);
        $this->assertSame('Rua', $translations['Street']);
        $this->assertSame('Número', $translations['Number']);
        $this->assertSame('Sem número', $translations['No number']);
        $this->assertSame('Complemento', $translations['Complement']);
        $this->assertSame('Apto, bloco, casa...', $translations['Apt, block, house...']);
        $this->assertSame('Bairro', $translations['Neighborhood']);
        $this->assertSame('Escolha o bairro', $translations['Choose a neighborhood']);
        $this->assertSame('Ponto de referência / Como chegar', $translations['Landmark / How to get there']);
        $this->assertSame('Ex: portão azul, ao lado da padaria', $translations['Ex: blue gate, next to the bakery']);
        $this->assertSame('Telefone de contato da entrega', $translations['Delivery contact phone']);
        $this->assertSame('CEP não encontrado, preencha manualmente', $translations['ZIP code not found, fill in manually']);
        $this->assertSame('Bairro não atendido ou não cadastrado', $translations['Neighborhood not served or not registered']);
        $this->assertSame('Consultando CEP...', $translations['Looking up ZIP code...']);
        $this->assertSame('Usar novo endereço', $translations['Use a new address']);
        $this->assertSame('Taxa de entrega', $translations['Delivery fee']);
        $this->assertSame('Cadastrar bairro', $translations['Register neighborhood']);
        $this->assertSame('Abrir no mapa', $translations['Open in maps']);
        $this->assertSame('Informe a taxa de entrega', $translations['Enter the delivery fee']);
    }
}
