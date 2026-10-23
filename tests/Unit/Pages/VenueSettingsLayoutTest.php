<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class VenueSettingsLayoutTest extends TestCase
{
    public function test_logo_sits_in_a_narrow_column_beside_the_address(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/Venue.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_12rem]', $page);
        $this->assertStringContainsString("__('Venue address')", $page);
        $this->assertStringContainsString(":title=\"__('Logo')\"", $page);
        $this->assertStringContainsString('relative aspect-square cursor-pointer overflow-hidden rounded-md border-2 border-dashed', $page);
        $this->assertStringContainsString('@drop.prevent="onLogoDrop"', $page);
        $this->assertStringContainsString('forceFormData: true', $page);
        $this->assertStringContainsString('useAutosaveForm(form, submit)', $page);
        $this->assertStringNotContainsString("__('Save Changes')", $page);
        $this->assertStringNotContainsString(":title=\"__('Logo URL')\"", $page);
        $this->assertStringNotContainsString('v-model="form.logo_url"', $page);
        $this->assertStringNotContainsString(":title=\"__('Operational Requirements')\"", $page);
        $this->assertStringNotContainsString(":title=\"__('Geolocation')\"", $page);
    }

    public function test_basic_information_includes_an_establishment_description_field(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/Venue.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Venue.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($page);
        $this->assertStringContainsString(":title=\"__('Basic Information')\"", $page);
        $this->assertStringContainsString('useAutosaveForm(form, submit)', $page);
        $this->assertStringContainsString('preserveScroll: true', $page);
        $this->assertStringNotContainsString("__('Save Changes')", $page);
        $this->assertStringContainsString('v-model="form.description"', $page);
        $this->assertStringContainsString("__('Venue name')", $page);
        $this->assertStringContainsString("__('Establishment description')", $page);
        $this->assertStringContainsString("__('(visible to the customer on the menu)')", $page);
        $this->assertStringContainsString("__('(Write a short, inviting text about your venue.)')", $page);
        $this->assertStringContainsString('font-normal italic text-muted-foreground', $page);
        $this->assertSame('Nome do estabelecimento', $translations['Venue name']);
        $this->assertSame('Descrição do estabelecimento', $translations['Establishment description']);
        $this->assertSame('Endereço do estabelecimento', $translations['Venue address']);
        $this->assertSame('(visível para o cliente no cardápio)', $translations['(visible to the customer on the menu)']);
        $this->assertSame(
            '(Escreva um texto curto e convidativo sobre o seu estabelecimento.)',
            $translations['(Write a short, inviting text about your venue.)'],
        );
    }
}
