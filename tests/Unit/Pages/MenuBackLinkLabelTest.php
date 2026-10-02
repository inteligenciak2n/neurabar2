<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class MenuBackLinkLabelTest extends TestCase
{
    public function test_combos_section_matches_the_other_menu_headers(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Combos.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('<section>', $page);
        $this->assertStringContainsString("__('Combos')", $page);
        $this->assertStringContainsString("__('Bundle products into a special-price offer.')", $page);
        $this->assertStringContainsString("__('The customer orders the combo as one item.')", $page);
        $this->assertStringContainsString('flex min-w-0 items-center gap-3', $page);
        $this->assertStringContainsString('shrink-0 font-heading text-3xl font-bold tracking-tight', $page);
        $this->assertStringNotContainsString('AppLayout', $page);
        $this->assertStringNotContainsString("← {{ __('Menu') }}", $page);
        $this->assertStringContainsString('grid min-w-0 flex-1 grid-cols-1 gap-4 sm:grid-cols-2', $page);
        $this->assertStringContainsString('font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100', $page);
        $this->assertStringContainsString('mt-1 text-xs text-muted-foreground', $page);
        $this->assertStringContainsString('v-for="item in combo.items"', $page);
        $this->assertStringContainsString('@click="toggleActive(combo)"', $page);
        $this->assertStringContainsString(':aria-checked="combo.active"', $page);
        $this->assertStringContainsString('route(\'menu.combos.toggle\'', $page);
        $this->assertStringContainsString('variant="success"', $page);
        $this->assertStringContainsString("__('Add Combo')", $page);
    }

    public function test_portuguese_back_link_says_cardapio(): void
    {
        foreach (['Modifiers', 'Products', 'Combos'] as $component) {
            $translations = json_decode(
                file_get_contents(dirname(__DIR__, 3)."/resources/translations/pt/{$component}.json"),
                true,
                flags: JSON_THROW_ON_ERROR,
            );

            $this->assertSame('Cardápio', $translations['Menu'], $component);
        }
    }

    public function test_portuguese_combos_helper_matches_the_requested_text(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Combos.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Monte combos com produtos e um preço especial.', $translations['Bundle products into a special-price offer.']);
        $this->assertSame('O cliente pede o pacote pronto de uma vez.', $translations['The customer orders the combo as one item.']);
        $this->assertSame('Novo Combo', $translations['New Combo']);
        $this->assertSame('Editar Combo', $translations['Edit Combo']);
        $this->assertSame('Nome do Combo', $translations['Combo Name']);
        $this->assertSame('Preço do Combo', $translations['Combo Price']);
        $this->assertSame('Descrição do Combo', $translations['Combo Description']);
        $this->assertSame('Status atual: :status', $translations['Current status: :status']);
        $this->assertSame('ligado', $translations['on']);
        $this->assertSame('desligado', $translations['off']);
    }

    public function test_combo_form_matches_the_product_form_layout(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Combos.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('grid grid-cols-1 items-start gap-4 lg:grid-cols-2', $page);
        $this->assertStringNotContainsString('lg:grid-cols-3', $page);
        $this->assertStringContainsString("__('Combo Name')", $page);
        $this->assertStringContainsString("__('Combo Description')", $page);
        $this->assertStringContainsString("__('Combo Price') }} (R$)", $page);
        $this->assertStringContainsString("__('Combo Name') }} <span class=\"text-destructive\">*</span></label>", $page);
        $this->assertStringContainsString('text-sm font-bold text-ocean-deep', $page);
        $this->assertStringContainsString("__('Items')", $page);
        $this->assertStringContainsString("__('Add Item')", $page);
        $this->assertStringContainsString("__('New Combo')", $page);
        $this->assertStringContainsString("__('Edit Combo')", $page);
        $this->assertStringContainsString('role="switch"', $page);
        $this->assertStringContainsString(':aria-checked="form.active"', $page);
        $this->assertStringContainsString("__('Current status: :status'", $page);
        $this->assertStringContainsString('bg-[#5c9a6c] focus:ring-[#5c9a6c]', $page);
        $this->assertStringContainsString('lg:border-l lg:border-border lg:pl-4', $page);
        $this->assertStringContainsString("'text-muted-foreground line-through': !form.active", $page);
        $this->assertStringNotContainsString('v-model="form.active" type="checkbox"', $page);
        $this->assertStringContainsString('font-heading text-lg font-bold tracking-tight', $page);
        $this->assertStringContainsString('mt-4 rounded-lg border-4 border-[#5c9a6c] p-4', $page);
        $this->assertStringContainsString('rounded-lg border-2 border-[#5c9a6c] bg-gray-50 p-3 shadow-sm', $page);
        $this->assertStringContainsString('v-for="(item, index) in form.items"', $page);
    }

    public function test_modifiers_header_shows_sales_copy_beside_the_title(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Modifiers.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString("__('Modifiers')", $page);
        $this->assertStringContainsString("__('Register extras and options for the customer to choose.')", $page);
        $this->assertStringContainsString("__('The customer customizes the order their way.')", $page);
        $this->assertStringContainsString('flex min-w-0 items-center gap-3', $page);
        $this->assertStringContainsString('shrink-0 font-heading text-3xl font-bold tracking-tight', $page);
        $this->assertStringContainsString('text-sm leading-snug text-muted-foreground', $page);
        $this->assertStringNotContainsString('max-w-xl text-xs leading-snug', $page);
        $this->assertMatchesRegularExpression(
            '/<AppCard>\s*<div class="mb-6 flex flex-wrap items-start justify-between gap-4">/s',
            $page,
        );
    }

    public function test_portuguese_modifiers_sales_copy_matches_the_requested_text(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Modifiers.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Modificadores de Pedidos', $translations['Modifiers']);
        $this->assertSame('Cadastre extras e opções para o cliente escolher.', $translations['Register extras and options for the customer to choose.']);
        $this->assertSame('O cliente personaliza o pedido do jeito dele.', $translations['The customer customizes the order their way.']);
    }

    public function test_modifier_options_are_indented_and_add_option_uses_gold(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Modifiers.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('<div class="pl-6">', $page);
        $this->assertStringContainsString('text-xs font-medium text-warm-gold hover:text-sand hover:underline', $page);
        $this->assertStringNotContainsString('text-xs font-medium text-primary hover:underline', $page);
        $this->assertStringContainsString(":label=\"__('Multiple Selection')\"", $page);
        $this->assertStringContainsString(":label=\"__('Required')\"", $page);
        $this->assertStringContainsString(":label=\"__('Inactive')\"", $page);
        $this->assertStringNotContainsString('label="Multi-select"', $page);
        $this->assertMatchesRegularExpression(
            '/flex flex-wrap items-center gap-2[\s\S]*group\.name[\s\S]*__\(\'Required\'\)[\s\S]*__\(\'Multiple Selection\'\)/s',
            $page,
        );
    }

    public function test_portuguese_modifier_badges_are_translated(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Modifiers.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Seleção múltipla', $translations['Multiple Selection']);
        $this->assertSame('Obrigatório', $translations['Required']);
        $this->assertSame('Inativo', $translations['Inactive']);
        $this->assertSame('Usado em', $translations['Used in']);
        $this->assertSame('Novo Modificador', $translations['New Modifier']);
        $this->assertSame('Adicionar Modificador', $translations['Add Modifier']);
        $this->assertSame('Nome do Modificador', $translations['Modifier Name']);
        $this->assertSame('Itens', $translations['Items']);
        $this->assertSame('Nome item', $translations['Item Name']);
        $this->assertSame('Adicionar item', $translations['Add Item']);
        $this->assertSame('Valor', $translations['Value']);
        $this->assertSame('Remover', $translations['Remove']);
    }

    public function test_modifier_groups_are_laid_out_in_columns(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Modifiers.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('grid grid-cols-1 gap-4 md:grid-cols-2', $page);
        $this->assertStringContainsString("__('Used in')", $page);
        $this->assertStringContainsString('v-for="product in group.products"', $page);
        $this->assertStringContainsString('grid grid-cols-2 gap-1.5', $page);
        $this->assertStringContainsString('rounded-md bg-gray-200', $page);
        $this->assertStringNotContainsString("group.products.map((p) => p.name).join(', ')", $page);
        $this->assertStringContainsString("__('Add Modifier')", $page);
        $this->assertStringContainsString("__('Modifier Name')", $page);
        $this->assertStringContainsString('mt-4 rounded-lg border-4 border-[#5c9a6c] p-4', $page);
        $this->assertStringContainsString('grid grid-cols-1 items-start gap-4 lg:grid-cols-2', $page);
        $this->assertStringContainsString("__('Items')", $page);
        $this->assertStringContainsString("__('Item Name')", $page);
        $this->assertStringContainsString("__('Add Item')", $page);
        $this->assertStringContainsString("__('Value')", $page);
        $this->assertStringContainsString('variant="success"', $page);
        $this->assertStringNotContainsString('!bg-blue-600', $page);
        $this->assertStringContainsString('modifierGroups.length && !showCreateGroup', $page);
        $this->assertStringContainsString('createGroupForm.options', $page);
        $this->assertMatchesRegularExpression(
            '/font-heading text-lg font-bold tracking-tight[\s\S]*__\(\'Modifier Name\'\)[\s\S]*font-heading text-lg font-bold tracking-tight[\s\S]*__\(\'Items\'\)/s',
            $page,
        );
    }

    public function test_combo_edit_and_delete_buttons_use_stacked_labels(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Combos.vue');

        $this->assertNotFalse($page);
        $this->assertSame(2, substr_count($page, 'flex flex-col leading-tight'));
        $this->assertStringContainsString("__('Combo')", $page);
        $this->assertStringContainsString("<span>{{ __('Edit') }}</span>", $page);
        $this->assertStringContainsString("<span>{{ __('Delete') }}</span>", $page);
        $this->assertStringNotContainsString("openEdit(combo)\">{{ __('Edit') }}</AppButton>", $page);
        $this->assertStringNotContainsString("confirmDelete(combo)\">{{ __('Delete') }}</AppButton>", $page);
    }

    public function test_modifier_edit_and_delete_buttons_use_stacked_labels(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Modifiers.vue');

        $this->assertNotFalse($page);
        $this->assertSame(4, substr_count($page, 'flex flex-col leading-tight'));
        $this->assertStringContainsString("__('Modifier')", $page);
        $this->assertStringContainsString("<span>{{ __('Items') }}</span>", $page);
        $this->assertStringContainsString("<span>{{ __('Edit') }}</span>", $page);
        $this->assertStringContainsString("<span>{{ __('Delete') }}</span>", $page);
        $this->assertStringNotContainsString("openEditGroup(group)\">{{ __('Edit') }}</AppButton>", $page);
        $this->assertStringNotContainsString("openEditOption(option)\">{{ __('Edit') }}</AppButton>", $page);
    }

    public function test_portuguese_combo_and_modifier_action_labels_are_translated(): void
    {
        $combos = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Combos.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $modifiers = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Modifiers.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Combo', $combos['Combo']);
        $this->assertSame('Adicionar Combo', $combos['Add Combo']);
        $this->assertSame('Modificador', $modifiers['Modifier']);
        $this->assertSame('Adicionar Modificador', $modifiers['Add Modifier']);
        $this->assertSame('Itens', $modifiers['Items']);
    }
}
