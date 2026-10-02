<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class MenuIndexHeaderTest extends TestCase
{
    public function test_menu_title_shows_a_two_line_hint_beside_cardapio(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Index.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString("{{ __('Menu') }}", $page);
        $this->assertStringContainsString('<AppButton size="sm" @click="scrollToCategories">{{ __(\'Categories\') }}</AppButton>', $page);
        $this->assertStringContainsString('<AppButton size="sm" @click="scrollToProducts">{{ __(\'Products\') }}</AppButton>', $page);
        $this->assertStringContainsString('<AppButton size="sm" @click="scrollToModifiers">{{ __(\'Modifiers\') }}</AppButton>', $page);
        $this->assertStringContainsString('<AppButton size="sm" @click="scrollToCombos">{{ __(\'Combos\') }}</AppButton>', $page);
        $this->assertStringContainsString("route('menu.preview.customer')", $page);
        $this->assertStringContainsString("route('menu.preview.attendant')", $page);
        $this->assertStringContainsString('<AppButton size="sm" variant="accent" :href="route(\'menu.preview.customer\')">{{ __(\'Customer version\') }}</AppButton>', $page);
        $this->assertStringContainsString('<AppButton size="sm" variant="accent" :href="route(\'menu.preview.attendant\')">{{ __(\'Attendant version\') }}</AppButton>', $page);
        $this->assertStringContainsString("__('Customer version')", $page);
        $this->assertStringContainsString("__('Attendant version')", $page);
        $this->assertStringContainsString('id="menu-categories"', $page);
        $this->assertStringContainsString('id="menu-products"', $page);
        $this->assertStringContainsString('id="menu-modifiers"', $page);
        $this->assertStringContainsString('id="menu-combos"', $page);
        $this->assertStringContainsString('scrollToCategories', $page);
        $this->assertStringContainsString('scrollToProducts', $page);
        $this->assertStringContainsString('scrollToModifiers', $page);
        $this->assertStringContainsString('scrollToCombos', $page);
        $this->assertStringContainsString('<Deferred :data="[\'products\', \'stations\', \'modifierGroups\']">', $page);
        $this->assertStringContainsString('<Deferred :data="[\'modifierGroups\']">', $page);
        $this->assertStringContainsString('<Deferred :data="[\'combos\', \'products\']">', $page);
        $this->assertStringContainsString('<Products', $page);
        $this->assertStringContainsString('<Modifiers', $page);
        $this->assertStringContainsString('<Combos', $page);
        $this->assertStringNotContainsString("route('menu.products.index')", $page);
        $this->assertStringNotContainsString("route('menu.modifier-groups.index')", $page);
        $this->assertStringNotContainsString("route('menu.combos.index')", $page);
        $this->assertStringNotContainsString('hover:underline', $page);
        $this->assertStringContainsString('flex min-w-0 items-center gap-3', $page);
        $this->assertStringContainsString('text-xs leading-tight', $page);
        $this->assertStringContainsString('shrink-0 font-heading text-4xl font-bold', $page);
        $this->assertStringContainsString("__('Add, edit and include photos of your products.')", $page);
        $this->assertStringContainsString("__('See the menu as the customer sees it and as the attendant sees it.')", $page);
        $this->assertStringNotContainsString("__('Menu Overview')", $page);
        $this->assertStringNotContainsString('mb-6 flex justify-end', $page);
        $this->assertStringContainsString("variant=\"success\" @click=\"openCreate\">{{ __('Add Category') }}", $page);
        $this->assertStringNotContainsString('pointer-events-none invisible', $page);
        $this->assertStringContainsString("__('Categories')", $page);
        $this->assertStringContainsString("__('View and organize')", $page);
        $this->assertStringContainsString("__('the categories on your menu')", $page);
        $this->assertStringContainsString('shrink-0 font-heading text-3xl font-bold tracking-tight', $page);
        $this->assertStringContainsString('h-1 w-16 rounded-full bg-warm-gold', $page);
        $this->assertStringContainsString('flex items-end justify-between gap-2', $page);
        $this->assertStringContainsString("__('Use the arrows (↑ ↓) to change')", $page);
        $this->assertStringContainsString("__('the category order on the menu')", $page);
        $this->assertStringContainsString('flex items-end gap-6', $page);
        $this->assertStringContainsString('block whitespace-nowrap', $page);
        $this->assertStringNotContainsString('w-[11rem] text-center text-xs leading-tight', $page);
        $this->assertStringContainsString('flex w-[11rem] justify-center gap-2', $page);
        $this->assertStringContainsString('ml-auto flex shrink-0 gap-2', $page);
        $this->assertStringContainsString('flex min-w-0 flex-1 items-start gap-3', $page);
        $this->assertStringNotContainsString('sm:flex-row sm:items-start sm:justify-between', $page);
        $this->assertStringContainsString("__('Edit')", $page);
        $this->assertStringContainsString("__('Delete')", $page);
        $this->assertStringContainsString("__('Category')", $page);
        $this->assertStringContainsString('flex flex-col leading-tight', $page);
        $this->assertStringContainsString('productsInColumns', $page);
        $this->assertStringContainsString('px-2 py-0.5 text-xs text-ocean-deep', $page);
        $this->assertStringContainsString('bg-warm-gold/20', $page);
        $this->assertStringContainsString('font-heading text-sm font-semibold leading-tight', $page);
        $this->assertStringContainsString("__('products')", $page);
        $this->assertStringNotContainsString('category.products.slice(0, 5)', $page);
        $this->assertStringContainsString('mt-4 rounded-lg border-4 border-[#5c9a6c] p-4', $page);
        $this->assertStringContainsString('v-if="categories.length && !showForm" class="divide-y divide-muted"', $page);
    }

    public function test_overview_products_are_chunked_three_per_column(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Index.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('const PRODUCTS_PER_COLUMN = 3;', $page);
        $this->assertStringContainsString('productsInColumns(category.products)', $page);
    }

    public function test_portuguese_menu_hint_matches_the_requested_copy(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Index.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Cardápio', $translations['Menu']);
        $this->assertSame('Adicione, edite e inclua fotos dos seus produtos.', $translations['Add, edit and include photos of your products.']);
        $this->assertSame('Veja o cardápio como o cliente vê e como o atendente vê.', $translations['See the menu as the customer sees it and as the attendant sees it.']);
        $this->assertSame('Categorias', $translations['Categories']);
        $this->assertSame('Visualize e organize', $translations['View and organize']);
        $this->assertSame('as categorias do seu cardápio', $translations['the categories on your menu']);
        $this->assertSame('Utilize as setas (↑ ↓) para alterar', $translations['Use the arrows (↑ ↓) to change']);
        $this->assertSame('a ordem das categorias no cardápio', $translations['the category order on the menu']);
        $this->assertSame('Versão Cliente', $translations['Customer version']);
        $this->assertSame('Versão Atendente', $translations['Attendant version']);
        $this->assertSame('Editar', $translations['Edit']);
        $this->assertSame('Excluir', $translations['Delete']);
        $this->assertSame('Categoria', $translations['Category']);
        $this->assertSame('Adicionar Categoria', $translations['Add Category']);
    }
}
