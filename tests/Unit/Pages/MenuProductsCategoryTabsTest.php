<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class MenuProductsCategoryTabsTest extends TestCase
{
    public function test_products_page_filters_categories_as_tabs(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('role="tablist"', $page);
        $this->assertStringContainsString('role="tab"', $page);
        $this->assertStringContainsString("__('All Categories')", $page);
        $this->assertStringContainsString('rounded-t-lg border-x border-t', $page);
        $this->assertStringContainsString('categoryTabClass', $page);
        $this->assertStringContainsString("__('Categories')", $page);
        $this->assertStringContainsString('ml-[4cm] select-none whitespace-nowrap px-4 py-2.5 text-sm font-medium text-gray-300 dark:text-gray-600', $page);
        $this->assertStringNotContainsString("__('Add Category')", $page);
        $this->assertStringNotContainsString('openCreateCategory', $page);
        $this->assertStringContainsString("variant=\"success\" @click=\"openCreate\">{{ __('Add Product') }}", $page);
        $this->assertStringContainsString('overflow-x-auto overflow-y-hidden', $page);
        $this->assertStringNotContainsString("{{ __('All') }}", $page);
        $this->assertStringNotContainsString('rounded-full px-3 py-1.5', $page);
        $this->assertStringContainsString("__('Manage products, turn them on or off, and change photos.')", $page);
        $this->assertStringContainsString("__('Add modifiers and variations to each item.')", $page);
        $this->assertStringContainsString('flex min-w-0 items-center gap-3', $page);
        $this->assertStringContainsString("__('Sort')", $page);
        $this->assertStringContainsString('M4 6h16M4 12h16M4 18h16', $page);
        $this->assertStringContainsString('text-[9px] font-normal leading-tight', $page);
        $this->assertStringContainsString("route('menu.products.reorder')", $page);
        $this->assertStringContainsString('@dragstart="onProductDragStart($event, product.id)"', $page);
    }

    public function test_portuguese_all_categories_label_matches_the_requested_copy(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Todas as Categorias', $translations['All Categories']);
        $this->assertSame('Categorias', $translations['Categories']);
        $this->assertSame('Gerencie os produtos, ative ou desative e troque as fotos.', $translations['Manage products, turn them on or off, and change photos.']);
        $this->assertSame('Inclua modificadores e variações em cada item.', $translations['Add modifiers and variations to each item.']);
        $this->assertSame('ordenar', $translations['Sort']);
        $this->assertArrayNotHasKey('All', $translations);
    }

    public function test_product_form_active_control_is_a_switch_in_the_header(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('role="switch"', $page);
        $this->assertStringContainsString(':aria-checked="form.active"', $page);
        $this->assertStringContainsString('@click="form.active = !form.active"', $page);
        $this->assertStringContainsString("__('Current status: :status'", $page);
        $this->assertStringContainsString("__('on')", $page);
        $this->assertStringContainsString("__('off')", $page);
        $this->assertStringContainsString('mb-3 flex flex-wrap items-center gap-3', $page);
        $this->assertStringContainsString('bg-[#5c9a6c] focus:ring-[#5c9a6c]', $page);
        $this->assertStringContainsString('text-[#4e7d5a] dark:text-[#7aab86]', $page);
        $this->assertStringNotContainsString('v-model="form.active" type="checkbox"', $page);
        $this->assertStringNotContainsString('mb-3 flex items-start justify-between gap-4', $page);
        $this->assertStringNotContainsString('bg-ocean-deep focus:ring-ocean-deep', $page);
    }

    public function test_product_list_uses_a_status_switch_and_strikes_inactive_names(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString(':aria-checked="product.active"', $page);
        $this->assertStringContainsString('@click="toggleActive(product)"', $page);
        $this->assertStringContainsString("product.active ? __('on') : __('off')", $page);
        $this->assertStringContainsString('flex flex-col items-center gap-0.5', $page);
        $this->assertStringContainsString('text-[10px] leading-tight', $page);
        $this->assertStringContainsString('font-heading text-sm font-semibold', $page);
        $this->assertStringContainsString('text-muted-foreground line-through dark:text-gray-400', $page);
        $this->assertStringContainsString("'line-through': !product.active", $page);
        $this->assertStringContainsString("'text-muted-foreground line-through': !form.active", $page);
        $this->assertStringNotContainsString("__('Deactivate')", $page);
        $this->assertStringNotContainsString("__('Activate')", $page);
    }

    public function test_product_list_meta_labels_use_warm_gold(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('R$ {{ Number(product.price).toFixed(2) }}', $page);
        $this->assertStringContainsString("__('Cat.:')", $page);
        $this->assertStringContainsString("__('Sector:')", $page);
        $this->assertStringContainsString('<span class="text-warm-gold">{{ __(\'Cat.:\') }}</span>', $page);
        $this->assertStringContainsString('<span class="text-warm-gold">{{ __(\'Sector:\') }}</span>', $page);
        $this->assertStringNotContainsString("__('Cat.: :name'", $page);
        $this->assertStringNotContainsString('text-primary dark:text-blue-300', $page);
        $this->assertStringNotContainsString('text-amber-700 dark:text-amber-400', $page);
    }

    public function test_portuguese_product_list_meta_labels_match_the_requested_copy(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Cat.:', $translations['Cat.:']);
        $this->assertSame('Setor:', $translations['Sector:']);
    }

    public function test_product_list_shows_modifier_and_variation_columns(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString("__('Modifiers:')", $page);
        $this->assertStringContainsString("__('No modifiers')", $page);
        $this->assertStringContainsString("__('Variations:')", $page);
        $this->assertStringContainsString("__('No variations')", $page);
        $this->assertStringContainsString('v-for="group in product.modifier_groups"', $page);
        $this->assertStringContainsString('inline-flex flex-wrap items-center gap-1', $page);
        $this->assertStringNotContainsString('modifierGroupNames(product)', $page);
        $this->assertStringNotContainsString('variationNames(product)', $page);
        $this->assertStringContainsString('v-for="variation in product.variations"', $page);
        $this->assertStringContainsString('truncate text-right">{{ variation.name }}</span>', $page);
        $this->assertStringContainsString('grid grid-cols-[minmax(0,1fr)_auto]', $page);
        $this->assertStringContainsString("toFixed(2).replace('.', ',')", $page);
        $this->assertStringContainsString('lg:flex-row lg:items-center lg:gap-4', $page);
        $this->assertStringContainsString('lg:ml-auto lg:shrink-0', $page);
        $this->assertStringContainsString('productPhotoSrc(product)', $page);
        $this->assertStringContainsString("__('No photo')", $page);
        $this->assertStringContainsString('size-14 shrink-0', $page);
        $this->assertStringContainsString('text-gray-300 dark:text-gray-600', $page);
        $this->assertStringNotContainsString('bg-muted dark:border-gray-600 dark:bg-gray-800', $page);
        $this->assertStringNotContainsString('lg:flex-row lg:items-start lg:justify-between', $page);
        $this->assertStringContainsString('text-xs text-muted-foreground', $page);
        $this->assertStringContainsString('rounded-md bg-primary/10 px-1.5 py-0.5 font-medium text-primary', $page);
    }

    public function test_portuguese_product_list_columns_match_the_requested_copy(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Modificadores:', $translations['Modifiers:']);
        $this->assertSame('sem modificadores', $translations['No modifiers']);
        $this->assertSame('Variações:', $translations['Variations:']);
        $this->assertSame('sem variações', $translations['No variations']);
        $this->assertSame('sem foto', $translations['No photo']);
    }

    public function test_portuguese_product_status_copy_matches_the_requested_text(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Status atual: :status', $translations['Current status: :status']);
        $this->assertSame('ligado', $translations['on']);
        $this->assertSame('desligado', $translations['off']);
    }

    public function test_product_form_is_ordered_in_three_columns(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('grid grid-cols-1 gap-4 lg:grid-cols-3', $page);
        $this->assertStringContainsString('lg:col-span-2', $page);
        $this->assertStringContainsString("__('Product Name')", $page);
        $this->assertStringContainsString("__('Description')", $page);
        $this->assertStringContainsString("__('Serves')", $page);
        $this->assertStringContainsString('changeServings', $page);
        $this->assertStringContainsString("__('Production Sector')", $page);
        $this->assertStringContainsString("__('Menu Category')", $page);
        $this->assertStringContainsString("__('Photo')", $page);
        $this->assertStringContainsString("__('Drag a photo here or click to upload.')", $page);
        $this->assertStringContainsString("__('Delete photo')", $page);
        $this->assertStringContainsString("__('Format: JPEG, PNG or WebP')", $page);
        $this->assertStringContainsString("__('A square image is preferred.')", $page);
        $this->assertStringContainsString('size-32 shrink-0', $page);
        $this->assertStringContainsString('flex items-start gap-3', $page);
        $this->assertStringContainsString('clearPhoto', $page);
        $this->assertStringContainsString('onPhotoDrop', $page);
        $this->assertStringContainsString("__('Modifier Groups')", $page);
        $this->assertStringContainsString("__('Variations')", $page);
        $this->assertStringContainsString("__('Save the product first to manage modifiers and variations.')", $page);
        $this->assertStringNotContainsString("__('Kitchen Station')", $page);

        $productName = strpos($page, "__('Product Name')");
        $description = strpos($page, "__('Description')");
        $serves = strpos($page, "__('Serves')");
        $price = strpos($page, "__('Price') }} (R$)");
        $category = strpos($page, "__('Menu Category')");
        $sector = strpos($page, "__('Production Sector')");
        $photo = strpos($page, "__('Photo')");
        $modifiers = strpos($page, "__('Modifier Groups')");
        $variations = strpos($page, "__('Variations')");

        $this->assertNotFalse($productName);
        $this->assertNotFalse($description);
        $this->assertNotFalse($serves);
        $this->assertNotFalse($price);
        $this->assertNotFalse($category);
        $this->assertNotFalse($sector);
        $this->assertNotFalse($photo);
        $this->assertNotFalse($modifiers);
        $this->assertNotFalse($variations);
        $this->assertLessThan($description, $productName);
        $this->assertLessThan($serves, $description);
        $this->assertLessThan($price, $serves);
        $this->assertLessThan($category, $price);
        $this->assertLessThan($sector, $category);
        $this->assertLessThan($photo, $sector);
        $this->assertLessThan($modifiers, $photo);
        $this->assertLessThan($variations, $modifiers);
    }

    public function test_portuguese_product_form_labels_match_the_requested_copy(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Nome do Produto', $translations['Product Name']);
        $this->assertSame('Categoria do Cardápio', $translations['Menu Category']);
        $this->assertSame('Setor de Produção', $translations['Production Sector']);
        $this->assertSame(
            'Salve o produto primeiro para gerenciar modificadores e variações.',
            $translations['Save the product first to manage modifiers and variations.'],
        );
        $this->assertSame('Foto', $translations['Photo']);
        $this->assertSame(
            'Arraste uma foto aqui ou clique para enviar.',
            $translations['Drag a photo here or click to upload.'],
        );
        $this->assertSame('Apagar foto', $translations['Delete photo']);
        $this->assertSame('Formato: JPEG, PNG ou WebP', $translations['Format: JPEG, PNG or WebP']);
        $this->assertSame('Preferível imagem quadrada.', $translations['A square image is preferred.']);
        $this->assertSame('Serve', $translations['Serves']);
        $this->assertSame('pessoa', $translations['person']);
        $this->assertSame('pessoas', $translations['people']);
    }

    public function test_product_catalog_loads_after_the_menu_overview(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Index.vue');
        $productsPage = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertNotFalse($productsPage);
        $this->assertStringContainsString('<Deferred :data="[\'products\', \'stations\', \'modifierGroups\']">', $page);
        $this->assertStringContainsString('<AppSkeleton :lines="6" />', $page);
        $this->assertStringContainsString('v-if="!filteredProducts.length && !showForm"', $productsPage);
        $this->assertStringContainsString('v-if="filteredProducts.length && !showForm"', $productsPage);
        $this->assertStringNotContainsString('isLoadingProducts', $productsPage);
    }

    public function test_portuguese_processing_label_is_translated(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Processando...', $translations['Processing...']);
    }

    public function test_save_button_is_on_the_left_and_unsaved_changes_are_prompted(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('flex justify-start gap-2 sm:col-span-2', $page);
        $this->assertStringContainsString('requestCloseForm', $page);
        $this->assertStringContainsString('hasUnsavedChanges', $page);
        $this->assertStringContainsString("__('You have unsaved changes. Do you want to save them?')", $page);
        $this->assertStringContainsString('saveChangesAndLeave', $page);
        $this->assertStringContainsString('discardChangesAndLeave', $page);
        $this->assertStringNotContainsString('flex justify-end gap-2 sm:col-span-2', $page);
    }

    public function test_portuguese_unsaved_changes_copy_matches_the_requested_text(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Alterações não salvas', $translations['Unsaved changes']);
        $this->assertSame('Há alterações não salvas. Deseja salvar?', $translations['You have unsaved changes. Do you want to save them?']);
        $this->assertSame('Não salvar', $translations["Don't save"]);
    }

    public function test_product_form_title_has_a_light_highlight_for_edit_and_new(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString("__('Edit Product')", $page);
        $this->assertStringContainsString("__('New Product')", $page);
        $this->assertStringContainsString('font-heading text-lg font-bold tracking-tight', $page);
        $this->assertStringContainsString('h-0.5 w-10 rounded-full bg-warm-gold', $page);
        $this->assertStringContainsString('mt-4 rounded-lg border-4 border-[#5c9a6c] p-4', $page);
    }

    public function test_portuguese_new_product_title_uses_the_requested_capitalization(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Novo Produto', $translations['New Product']);
        $this->assertSame('Editar produto', $translations['Edit Product']);
    }

    public function test_modifier_groups_and_variations_titles_have_a_light_highlight(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertSame(3, substr_count($page, 'h-0.5 w-10 rounded-full bg-warm-gold'));
        $this->assertStringContainsString("__('Modifier Groups')", $page);
        $this->assertStringContainsString("__('Variations')", $page);

        $modifiers = strpos($page, "__('Modifier Groups')");
        $variations = strpos($page, "__('Variations')");
        $goldBars = [];
        $offset = 0;

        while (($pos = strpos($page, 'h-0.5 w-10 rounded-full bg-warm-gold', $offset)) !== false) {
            $goldBars[] = $pos;
            $offset = $pos + 1;
        }

        $this->assertCount(3, $goldBars);
        $this->assertLessThan($goldBars[1], $modifiers);
        $this->assertGreaterThan($goldBars[0], $modifiers);
        $this->assertLessThan($goldBars[2], $variations);
        $this->assertGreaterThan($goldBars[1], $variations);
    }

    public function test_variation_edit_and_delete_buttons_are_on_the_right(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('editingVariationId !== variation.id" class="flex items-center justify-between gap-2"', $page);
        $this->assertStringContainsString('flex shrink-0 gap-1', $page);
        $this->assertStringContainsString('openEditVariation(variation)', $page);
        $this->assertStringContainsString('confirmDeleteVariation(variation)', $page);
        $this->assertStringNotContainsString('editingVariationId !== variation.id" class="flex flex-col gap-2"', $page);
    }

    public function test_modifier_groups_open_a_picker_window(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('openModifierPicker', $page);
        $this->assertStringContainsString(':show="showModifierPicker"', $page);
        $this->assertStringContainsString("__('Add Modifiers')", $page);
        $this->assertStringContainsString("__('Selected modifiers:')", $page);
        $this->assertStringContainsString("__('Add Variations')", $page);
        $this->assertStringContainsString('hover:text-warm-gold', $page);
        $this->assertStringContainsString('bg-ocean-deep text-sand', $page);
        $this->assertStringContainsString('rounded-md bg-primary px-3 py-1.5 text-sm text-white', $page);
        $this->assertStringContainsString('toggleGroupInSync(group.id)', $page);
        $this->assertStringContainsString("__('Done')", $page);
        $this->assertStringContainsString('submitSync(editingProduct.value)', $page);
        $this->assertStringNotContainsString("__('Save Modifiers')", $page);
        $this->assertStringNotContainsString('@click="submitSync(editingProduct)"', $page);
        $this->assertStringNotContainsString("__('Add Variation')", $page);
        $this->assertStringNotContainsString('openModifierPicker">{{ __(\'Select\') }}', $page);
        $this->assertStringNotContainsString('class="sr-only"', $page);
    }

    public function test_portuguese_modifier_and_variation_actions_match_the_requested_copy(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Adicionar modificadores', $translations['Add Modifiers']);
        $this->assertSame('Adicionar variações', $translations['Add Variations']);
        $this->assertSame('Salvar', $translations['Save']);
        $this->assertSame('Pronto', $translations['Done']);
        $this->assertSame('Modificadores selecionados:', $translations['Selected modifiers:']);
    }

    public function test_modifier_section_places_help_text_beside_the_title(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString("__('Selected modifiers:')", $page);

        $title = strpos($page, "__('Modifier Groups')");
        $help = strpos($page, "__('Select which modifier groups apply to this product.')");
        $addButton = strpos($page, "__('Add Modifiers')");
        $selectedLabel = strpos($page, "__('Selected modifiers:')");
        $chips = strpos($page, 'rounded-md bg-primary px-3 py-1.5 text-sm text-white');

        $this->assertNotFalse($title);
        $this->assertNotFalse($help);
        $this->assertNotFalse($addButton);
        $this->assertNotFalse($selectedLabel);
        $this->assertNotFalse($chips);
        $this->assertLessThan($help, $title);
        $this->assertLessThan($addButton, $help);
        $this->assertLessThan($selectedLabel, $addButton);
        $this->assertLessThan($chips, $selectedLabel);
    }

    public function test_product_and_variation_actions_use_stacked_labels_like_categories(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/Products.vue');

        $this->assertNotFalse($page);
        $this->assertSame(4, substr_count($page, 'flex flex-col leading-tight'));
        $this->assertStringContainsString("__('Product')", $page);
        $this->assertStringContainsString("__('Variation')", $page);
        $this->assertStringContainsString("<span>{{ __('Edit') }}</span>", $page);
        $this->assertStringContainsString("<span>{{ __('Delete') }}</span>", $page);
        $this->assertStringNotContainsString("openEdit(product)\">{{ __('Edit') }}</AppButton>", $page);
        $this->assertStringNotContainsString("confirmDelete(product)\">{{ __('Delete') }}</AppButton>", $page);
        $this->assertStringNotContainsString("openEditVariation(variation)\">{{ __('Edit') }}</AppButton>", $page);
        $this->assertStringNotContainsString("confirmDeleteVariation(variation)\">{{ __('Delete') }}</AppButton>", $page);
    }

    public function test_portuguese_product_action_labels_are_translated(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Produto', $translations['Product']);
        $this->assertSame('Adicionar Produto', $translations['Add Product']);
        $this->assertSame('Variação', $translations['Variation']);
        $this->assertSame('Editar', $translations['Edit']);
        $this->assertSame('Excluir', $translations['Delete']);
    }
}
