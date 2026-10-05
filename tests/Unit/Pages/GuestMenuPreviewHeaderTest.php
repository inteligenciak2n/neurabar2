<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class GuestMenuPreviewHeaderTest extends TestCase
{
    public function test_customer_preview_puts_the_label_and_back_button_in_the_header(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/Menu.vue');
        $layout = file_get_contents(dirname(__DIR__, 3).'/resources/js/Layouts/GuestLayout.vue');

        $this->assertNotFalse($page);
        $this->assertNotFalse($layout);
        $this->assertStringContainsString('<template v-if="preview" #headerTitle>', $page);
        $this->assertStringContainsString("__('Customer version - Table')", $page);
        $this->assertStringContainsString("__('Customer version - Delivery')", $page);
        $this->assertStringContainsString("route('menu.preview.customer.delivery')", $page);
        $this->assertStringContainsString('<template v-if="preview" #headerSubtitle>', $page);
        $this->assertStringContainsString("__('Adaptive screen for phone, tablet or computer')", $page);
        $this->assertStringContainsString('<template v-if="preview" #header>', $page);
        $this->assertStringContainsString(':href="route(\'menu.index\')"', $page);
        $this->assertStringContainsString("__('Back')", $page);
        $this->assertStringContainsString('<slot name="headerTitle" />', $layout);
        $this->assertStringContainsString('<slot name="headerSubtitle" />', $layout);
        $this->assertStringContainsString('text-[10px] font-normal leading-tight text-gray-400', $layout);
        $this->assertStringContainsString('<slot name="header" />', $layout);
        $this->assertStringContainsString('previewWatermark', $layout);
        $this->assertStringContainsString('pointer-events-none absolute inset-0 z-0 overflow-hidden', $layout);
        $this->assertStringContainsString("previewWatermark ? 'z-10 rounded-2xl border-2 border-white/40 bg-muted p-3' : ''", $layout);
        $this->assertStringContainsString(':preview-watermark="preview ? __(\'Table version\') : null"', $page);
        $this->assertStringContainsString('#frameLabel', $page);
        $this->assertStringContainsString('const tableLabel = computed', $page);
        $this->assertStringContainsString("__('Table no. :number'", $page);
        $this->assertStringContainsString('bg-primary', $page);
        $this->assertStringContainsString('border-2 border-ocean-deep', $page);
        $this->assertStringContainsString('$slots.frameLabel', $layout);
        $this->assertStringContainsString('mb-2 flex justify-center', $layout);
        $this->assertStringNotContainsString('-translate-y-1/2', $layout);
        $this->assertStringNotContainsString('bg-blue-600', $page);
        $this->assertStringContainsString("\$slots.headerTitle ? 'py-2' : 'py-4'", $layout);
        $this->assertStringContainsString('v-if="!$slots.headerTitle"', $layout);
        $this->assertStringContainsString('v-else-if="!venue"', $layout);
        $this->assertStringNotContainsString('v-else class="h-8 w-auto text-primary"', $layout);
        $this->assertStringNotContainsString('font-heading text-sm font-bold leading-tight text-ocean-deep">-</span>', $layout);
        $this->assertStringNotContainsString('Preview — customer view', $page);
        $this->assertStringNotContainsString('Back to menu', $page);
    }

    public function test_customer_preview_moves_the_venue_brand_above_the_menu(): void
    {
        $catalog = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/GuestMenuCatalog.vue');

        $this->assertNotFalse($catalog);
        $this->assertStringContainsString('v-if="preview" class="mb-4 grid grid-cols-[minmax(0,1fr)_7rem] items-start gap-3"', $catalog);
        $this->assertStringContainsString('{{ venue.name }}', $catalog);
        $this->assertStringContainsString('{{ venue.description }}', $catalog);
        $this->assertStringContainsString('venue?.logo_url && !logoFailed', $catalog);
        $this->assertStringContainsString('aspect-square overflow-hidden rounded-lg bg-muted shadow-card', $catalog);
        $this->assertStringContainsString('h-full w-full object-cover', $catalog);
        $this->assertStringContainsString('sticky top-0 z-20 bg-muted', $catalog);
        $this->assertStringNotContainsString('mb-4 grid grid-cols-3 items-center gap-3', $catalog);
    }

    public function test_product_photo_sits_against_the_card_edge_without_crushing_text(): void
    {
        $catalog = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/GuestMenuCatalog.vue');

        $this->assertNotFalse($catalog);
        $this->assertStringContainsString('flex min-h-[4.5rem] items-stretch', $catalog);
        $this->assertStringContainsString("product.image_url ? '' : 'px-3 py-2'", $catalog);
        $this->assertStringContainsString('h-[4.5rem] w-[4.5rem] shrink-0 self-stretch overflow-hidden bg-muted', $catalog);
        $this->assertStringContainsString("product.image_url ? 'px-3 py-2' : ''", $catalog);
        $this->assertStringContainsString('flex min-h-0 min-w-0 flex-1 flex-col', $catalog);
        $this->assertStringContainsString('mt-auto flex items-baseline justify-between gap-2', $catalog);
        $this->assertStringContainsString('flex min-w-0 items-start gap-2', $catalog);
        $this->assertStringContainsString('v-if="product.variations?.length"', $catalog);
        $this->assertStringContainsString('v-for="variation in product.variations"', $catalog);
        $this->assertStringContainsString('<span class="min-w-0 truncate">{{ variation.name }}</span>', $catalog);
        $this->assertStringContainsString('<span class="text-[0.7em] font-normal opacity-60">R$</span>', $catalog);
        $this->assertStringContainsString('{{ Number(variation.price).toFixed(2) }}', $catalog);
        $this->assertStringNotContainsString('<span class="shrink-0">R$ {{ Number(variation.price).toFixed(2) }}</span>', $catalog);
        $this->assertStringContainsString('flex items-baseline justify-end gap-1', $catalog);
        $this->assertStringNotContainsString('R$ {{ Number(variation.price).toFixed(2) }} {{ variation.name }}', $catalog);
        $this->assertStringContainsString('grid grid-cols-1 gap-2', $catalog);
        $this->assertStringContainsString('v-for="category in categories"', $catalog);
        $this->assertStringContainsString('data-menu-category', $catalog);
        $this->assertStringContainsString('scrollToCategory', $catalog);
        $this->assertStringNotContainsString('sm:grid-cols-2 pb-24', $catalog);
        $this->assertStringNotContainsString("product.image_url ? 'grid grid-cols-3 items-stretch' : 'p-4'", $catalog);
        $this->assertStringNotContainsString('col-span-1 min-h-[5.5rem] overflow-hidden bg-muted', $catalog);
        $this->assertStringContainsString('font-body text-sm font-bold leading-snug text-ocean-deep', $catalog);
        $this->assertStringContainsString("__('Price:')", $catalog);
        $this->assertStringContainsString('flex items-baseline justify-between gap-2', $catalog);
        $this->assertStringContainsString('mt-0.5 text-xs font-normal leading-snug text-muted-foreground', $catalog);
        $this->assertStringContainsString('line-clamp-1', $catalog);
        $this->assertStringNotContainsString('mt-1.5 font-heading text-sm font-bold text-primary', $catalog);
        $this->assertStringNotContainsString('flex items-start justify-between gap-2', $catalog);
        $this->assertStringNotContainsString('w-20 min-h-[5rem] shrink-0 self-stretch overflow-hidden bg-muted', $catalog);
        $this->assertStringNotContainsString('size-14 shrink-0 overflow-hidden rounded-lg bg-muted', $catalog);
    }

    public function test_category_chips_hide_the_scrollbar_and_fade_the_edges(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/Menu.vue');
        $delivery = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/Delivery/Menu.vue');
        $catalog = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/GuestMenuCatalog.vue');
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/CategoryChipRow.vue');

        $this->assertNotFalse($page);
        $this->assertNotFalse($delivery);
        $this->assertNotFalse($catalog);
        $this->assertNotFalse($component);
        $this->assertStringContainsString('<GuestMenuCatalog', $page);
        $this->assertStringContainsString('<GuestMenuCatalog', $delivery);
        $this->assertStringContainsString('<CategoryChipRow v-else :active-id="selectedCategoryId">', $catalog);
        $this->assertStringContainsString('data-category-chip', $catalog);
        $this->assertStringContainsString(':data-category-id="category.id"', $catalog);
        $this->assertStringNotContainsString('overflow-x-auto pb-1 border-b border-muted', $page);
        $this->assertStringContainsString('overflow-x-auto overscroll-x-contain touch-pan-x', $component);
        $this->assertStringContainsString('@pointerdown="onPointerDown"', $component);
        $this->assertStringContainsString('addEventListener(\'wheel\'', $component);
        $this->assertStringContainsString('cursor-grab', $component);
        $this->assertStringContainsString('[scrollbar-width:none]', $component);
        $this->assertStringContainsString('[&::-webkit-scrollbar]:hidden', $component);
        $this->assertStringContainsString('bg-gradient-to-r from-muted to-transparent', $component);
        $this->assertStringContainsString('bg-gradient-to-l from-muted to-transparent', $component);
        $this->assertStringContainsString('canScrollLeft', $component);
        $this->assertStringContainsString('canScrollRight', $component);
        $this->assertStringContainsString('pointer-events-none', $component);
        $this->assertStringContainsString('scrollActiveChipIntoView', $component);
        $this->assertStringContainsString('watch(() => props.activeId, scheduleScrollActiveChip)', $component);
        $this->assertStringContainsString('el.setPointerCapture?.(event.pointerId)', $component);
        $this->assertStringContainsString('Math.abs(delta) <= 8', $component);
        $this->assertStringContainsString('programmaticScrollUntil', $catalog);
        $this->assertStringContainsString('window.scrollTo', $catalog);
        $this->assertStringContainsString("el.scrollTo({ left: nextLeft, behavior: 'smooth' })", $component);
    }

    public function test_portuguese_customer_version_label_matches_the_requested_copy(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Menu.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Versão Cliente - Mesa', $translations['Customer version - Table']);
        $this->assertSame('Versão cliente - Delivery', $translations['Customer version - Delivery']);
        $this->assertSame('Tela adaptável para celular, tablet ou computador', $translations['Adaptive screen for phone, tablet or computer']);
        $this->assertSame('Voltar', $translations['Back']);
        $this->assertSame('Valor:', $translations['Price:']);
        $this->assertSame('Cardápio indisponível', $translations['Menu not available']);
        $this->assertSame('Este estabelecimento ainda não tem itens no cardápio.', $translations['This venue has no menu items yet.']);
        $this->assertSame('Nenhum item nesta categoria', $translations['No items in this category']);
        $this->assertSame('Volte mais tarde.', $translations['Check back later.']);
        $this->assertSame('Versão Mesa', $translations['Table version']);
        $this->assertSame('Mesa nº :number', $translations['Table no. :number']);
        $this->assertSame('Versão Delivery', $translations['Delivery version']);
        $this->assertSame('Finalizar pedido', $translations['Checkout']);
        $this->assertSame('Exibir itens do pedido', $translations['View Cart']);
        $this->assertSame('Visualizar pedido', $translations['View order']);
    }

    public function test_table_preview_uses_cart_checkout_and_links_to_delivery(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/Menu.vue');
        $delivery = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/Delivery/Menu.vue');
        $cart = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/CartPanel.vue');

        $this->assertNotFalse($page);
        $this->assertNotFalse($delivery);
        $this->assertNotFalse($cart);
        $this->assertStringContainsString('<CartPanel', $page);
        $this->assertStringContainsString(':preview="preview"', $page);
        $this->assertStringContainsString('v-if="cartCount > 0"', $page);
        $this->assertStringContainsString('<Teleport to="body">', $page);
        $this->assertStringContainsString('fixed bottom-24 left-1/2 z-50', $page);
        $this->assertStringContainsString('border-4 border-warm-gold', $page);
        $this->assertStringContainsString('<Teleport to="body">', $delivery);
        $this->assertStringContainsString('fixed bottom-24 left-1/2 z-50', $delivery);
        $this->assertStringContainsString('border-4 border-warm-gold', $delivery);
        $this->assertStringNotContainsString('v-if="!preview && cartCount > 0"', $page);
        $this->assertStringContainsString('<DeliveryCheckoutPanel', $delivery);
        $this->assertStringContainsString(':venue="venue"', $delivery);
        $this->assertStringContainsString('<GuestMenuCatalog', $delivery);
        $this->assertStringNotContainsString('#frameLabel', $delivery);
        $this->assertStringContainsString("__('Customer version - Delivery')", $delivery);
        $this->assertStringContainsString(':preview-watermark="preview ? __(\'Delivery version\') : null"', $delivery);
        $this->assertStringContainsString("route('menu.preview.customer')", $delivery);
        $this->assertStringContainsString(':editing-item="editingItem"', $page);
        $this->assertStringContainsString('@select-item="openCartItem"', $page);
        $this->assertStringContainsString(':editing-item="editingItem"', $delivery);
        $this->assertStringContainsString('@select-item="openCartItem"', $delivery);
        $this->assertStringContainsString("emit('select-item', index)", $cart);
        $catalog = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/GuestMenuCatalog.vue');
        $this->assertNotFalse($catalog);
        $this->assertStringContainsString('flex min-h-[4.5rem] items-stretch', $catalog);
        $this->assertStringContainsString('h-[4.5rem] w-[4.5rem] shrink-0 self-stretch overflow-hidden bg-muted', $catalog);
        $this->assertStringContainsString('grid grid-cols-1 gap-2', $catalog);
        $this->assertStringContainsString("__('Price:')", $catalog);
        $this->assertStringContainsString("__('View order')", $delivery);
        $this->assertStringContainsString("__('This venue has no menu items yet.')", $delivery);
        $this->assertStringNotContainsString("__('View Cart')", $delivery);
        $this->assertStringNotContainsString("__('This venue has no items available for delivery yet.')", $delivery);
        $this->assertStringNotContainsString('sm:grid-cols-2 pb-24', $catalog);
        $this->assertStringNotContainsString('rounded-xl bg-white p-4 shadow-card text-left', $catalog);
    }
}
