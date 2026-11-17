<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class GuestCallWaiterTest extends TestCase
{
    public function test_customer_table_menu_places_call_waiter_above_the_catalog(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/Menu.vue');
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/GuestCallWaiter.vue');
        $catalog = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/GuestMenuCatalog.vue');

        $this->assertNotFalse($page);
        $this->assertNotFalse($component);
        $this->assertNotFalse($catalog);
        $this->assertStringContainsString('<GuestCallWaiter', $page);
        $this->assertStringContainsString('<GuestCatalogDivider', $page);
        $this->assertStringContainsString('v-if="hasDirectWaiter || hasMenu"', $page);
        $this->assertStringContainsString('#above-categories', $page);
        $this->assertStringContainsString(':catalog-enabled="hasMenu"', $page);
        $this->assertStringContainsString(':show-category-chips="hasMenu"', $page);
        $this->assertStringContainsString('data-call-waiter', $component);
        $this->assertStringNotContainsString('defaultChips', $component);
        $this->assertStringNotContainsString("__('Request the bill')", $component);
        $this->assertStringContainsString('props.phrases', $component);
        $this->assertStringContainsString('v-if="chips.length || preview"', $component);
        $this->assertStringContainsString('/signal', $component);
        $this->assertStringContainsString('/cancel', $component);
        $this->assertStringContainsString('catalogEnabled && !categories.length', $catalog);
        $this->assertStringContainsString('v-if="preview"', $component);
        $this->assertStringContainsString('data-register-phrases', $component);
        $this->assertStringContainsString("route('settings.waiter-call-phrases.index')", $component);
        $this->assertStringContainsString("__('Register your own phrases here')", $component);
        $this->assertStringContainsString(':phrases="waiterCallPhrases"', $page);
    }

    public function test_portuguese_call_waiter_copy_matches_the_requested_layout(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/GuestCallWaiter.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Chamar garçom', $translations['Call waiter']);
        $this->assertSame('Diga o que precisa e já avisamos a equipe', $translations['Tell us what you need and we will notify the team']);
        $this->assertSame('O que você precisa?', $translations['What do you need?']);
        $this->assertSame('Garçom chamado!', $translations['Waiter called!']);
        $this->assertSame('Cancelar chamado', $translations['Cancel call']);
        $this->assertSame('Chamado às :time', $translations['Called at :time']);
        $this->assertSame('Cadastre aqui suas próprias frases', $translations['Register your own phrases here']);
    }

    public function test_catalog_divider_uses_a_gentle_moving_arrow(): void
    {
        $divider = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/GuestCatalogDivider.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/GuestCatalogDivider.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $tailwind = file_get_contents(dirname(__DIR__, 3).'/tailwind.config.js');

        $this->assertNotFalse($divider);
        $this->assertNotFalse($tailwind);
        $this->assertStringContainsString('data-catalog-divider', $divider);
        $this->assertStringContainsString('animate-nudge', $divider);
        $this->assertStringContainsString("__('Scroll to see the menu')", $divider);
        $this->assertStringContainsString("__('Menu')", $divider);
        $this->assertStringContainsString('scrollIntoView', $divider);
        $this->assertStringContainsString('nudge: \'nudge 1.6s ease-in-out infinite\'', $tailwind);
        $this->assertSame('Role para ver o cardápio', $translations['Scroll to see the menu']);
        $this->assertSame('Cardápio', $translations['Menu']);
    }
}
