<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class PreviewAttendantCatalogTest extends TestCase
{
    public function test_attendant_preview_uses_the_guest_delivery_catalog_layout(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Menu/PreviewAttendant.vue');
        $delivery = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/Delivery/Menu.vue');

        $this->assertNotFalse($page);
        $this->assertNotFalse($delivery);
        $this->assertStringContainsString("import AppLayout from '@/Layouts/AppLayout.vue'", $page);
        $this->assertStringContainsString("import GuestLayout from '@/Layouts/GuestLayout.vue'", $page);
        $this->assertStringContainsString('preview ? GuestLayout : AppLayout', $page);
        $this->assertStringContainsString('<GuestMenuCatalog', $page);
        $this->assertStringContainsString('#above-categories', $page);
        $this->assertStringContainsString('data-mode-tabs', $page);
        $this->assertStringContainsString('data-mode-order', $page);
        $this->assertStringContainsString('data-mode-status', $page);
        $this->assertStringContainsString('min-h-16', $page);
        $this->assertStringContainsString('headerCompact', $page);
        $this->assertStringContainsString('HEADER_COMPACT_AFTER_PX', $page);
        $this->assertStringContainsString('HEADER_EXPAND_BEFORE_PX', $page);
        $this->assertStringContainsString('data-compact-header', $page);
        $this->assertStringNotContainsString('window.scrollY > 16', $page);
        $this->assertStringNotContainsString('transition-all duration-200', $page);
        $this->assertStringContainsString('min-h-8 px-2 py-1 text-sm font-semibold', $page);
        $this->assertStringContainsString("mainTab = ref('order')", $page);
        $this->assertStringContainsString("__('New order')", $page);
        $this->assertStringContainsString("__('Check status')", $page);
        $this->assertStringContainsString('data-status-board', $page);
        $this->assertStringContainsString('data-status-lookup', $page);
        $this->assertStringContainsString('data-status-tab-query', $page);
        $this->assertStringContainsString('data-status-location-query', $page);
        $this->assertStringContainsString('data-status-identifier-query', $page);
        $this->assertStringContainsString('data-status-summary', $page);
        $this->assertStringContainsString('data-undelivered-items', $page);
        $this->assertStringContainsString('data-delivered-items', $page);
        $this->assertStringContainsString('statusLookupActive', $page);
        $this->assertStringContainsString('itemIsDelivered', $page);
        $this->assertStringContainsString("__('Type the tab')", $page);
        $this->assertStringContainsString("__('Type the table')", $page);
        $this->assertStringContainsString("__('Delivered')", $page);
        $this->assertStringContainsString("__('Not delivered')", $page);
        $this->assertStringContainsString("__('With orders')", $page);
        $this->assertStringContainsString("__('Without orders')", $page);
        $this->assertStringContainsString('statusTabOptions', $page);
        $this->assertStringContainsString('statusLocationOptions', $page);
        $this->assertStringContainsString('statusTabPickerItems', $page);
        $this->assertStringContainsString('statusLocationPickerItems', $page);
        $this->assertStringContainsString('groupStatusPickerItems', $page);
        $this->assertStringContainsString("openPicker === 'status-tab'", $page);
        $this->assertStringContainsString("openPicker === 'status-location'", $page);
        $this->assertStringContainsString('data-status-tab-options', $page);
        $this->assertStringContainsString('data-status-location-options', $page);
        $this->assertStringContainsString("tone: 'muted'", $page);
        $this->assertStringContainsString('chooseStatusTab', $page);
        $this->assertStringContainsString('chooseStatusLocation', $page);
        $this->assertStringContainsString('statusLookupBy', $page);
        $this->assertStringContainsString("statusLookupBy.value = 'tab'", $page);
        $this->assertStringContainsString("statusLookupBy.value = 'location'", $page);
        $this->assertStringContainsString('locateStatusLocationForTab', $page);
        $this->assertStringContainsString('attendancesForTab', $page);
        $this->assertStringContainsString('attendancesForLocation', $page);
        $this->assertStringContainsString('locatedStatusLocationLabel', $page);
        $this->assertStringContainsString('statusResultCards', $page);
        $this->assertStringContainsString('isOrderMode && (needsTab || needsLocation || missingRequiredLocation || showOrderPanel)', $page);
        $this->assertStringContainsString('missingRequiredLocation', $page);
        $this->assertStringContainsString('data-add-location', $page);
        $this->assertStringContainsString("__('Add consumption location')", $page);
        $this->assertStringContainsString("route('attendances.locations.store')", $page);
        $this->assertStringContainsString('deliveryDataComplete', $page);
        $this->assertStringContainsString('data-delivery-status', $page);
        $this->assertStringContainsString('data-delivery-ready', $page);
        $this->assertStringContainsString("__('Delivery data pending')", $page);
        $this->assertStringContainsString("__('Data OK')", $page);
        $this->assertStringContainsString('focusCustomerSection', $page);
        $this->assertStringContainsString('bg-red-600 text-white', $page);
        $this->assertStringContainsString('bg-emerald-600 text-white', $page);
        $this->assertStringContainsString('show-category-chips', $page);
        $this->assertStringContainsString('data-channel-tabs', $page);
        $this->assertStringContainsString('role="tablist"', $page);
        $this->assertStringContainsString('channelTabStyle', $page);
        $this->assertStringContainsString("opacity: '0.4'", $page);
        $this->assertStringContainsString("color: '#9ca3af'", $page);
        $this->assertStringContainsString('text-gray-400 border-gray-200 opacity-40', $page);
        $this->assertStringContainsString('channelPanelStyle', $page);
        $this->assertStringContainsString('rounded-t-lg', $page);
        $this->assertStringContainsString(':sticky-panel-style="channelPanelStyle"', $page);
        $this->assertStringContainsString(':sticky-top="catalogStickyTop"', $page);
        $this->assertStringContainsString('updateCatalogStickyTop', $page);
        $this->assertStringContainsString('data-app-nav', file_get_contents(dirname(__DIR__, 3).'/resources/js/Layouts/AppLayout.vue'));
        $this->assertStringContainsString('data-page-header', file_get_contents(dirname(__DIR__, 3).'/resources/js/Layouts/AppLayout.vue'));
        $this->assertStringContainsString('selectionByChannel', $page);
        $this->assertStringContainsString('sessionStorage', $page);
        $this->assertStringContainsString('openContextPicker', $page);
        $this->assertStringContainsString('visibleStatusAttendances', $page);
        $this->assertStringContainsString('selectedChannelId', $page);
        $this->assertStringContainsString('#panel-fields', $page);
        $this->assertStringContainsString('data-channel-requirements', $page);
        $this->assertStringContainsString('data-requirement-columns', $page);
        $this->assertStringContainsString('grid-cols-2', $page);
        $this->assertStringContainsString('grid-cols-[auto_auto_minmax(0,1fr)]', $page);
        $this->assertStringContainsString('col-start-2', $page);
        $this->assertStringContainsString('col-span-2', $page);
        $this->assertStringContainsString('data-tab-summary', $page);
        $this->assertStringContainsString('data-location-summary', $page);
        $this->assertLessThan(
            strpos($page, 'data-tab-field'),
            strpos($page, 'data-location-field'),
        );
        $this->assertLessThan(
            strpos($page, 'data-order-summary'),
            strpos($page, 'data-tab-field'),
        );
        $this->assertStringContainsString('tabSummaryVisible', $page);
        $this->assertStringContainsString('const keepLocation = Boolean(selectedLocationId.value);', $page);
        $this->assertStringNotContainsString('selectedLocationId.value && tabSummaryVisible.value', $page);
        $this->assertStringContainsString('data-new-tab', $page);
        $this->assertGreaterThanOrEqual(2, substr_count($page, 'data-new-tab'));
        $this->assertStringContainsString('flex min-h-10 items-end justify-between gap-2', $page);
        $this->assertStringContainsString('data-active-tab-line', $page);
        $this->assertStringContainsString('data-order-tab-group', $page);
        $this->assertStringContainsString('orderSummaryGroups', $page);
        $this->assertStringContainsString('bg-emerald-100', $page);
        $this->assertStringContainsString('bg-gray-200', $page);
        $this->assertStringContainsString('showOrderPanel', $page);
        $this->assertStringContainsString('const showOrderPanel = computed(() => orderSummaryLines.value.length > 0);', $page);
        $this->assertLessThan(
            strpos($page, 'data-new-tab'),
            strpos($page, 'data-order-summary'),
        );
        $this->assertStringContainsString('data-tab-picker', $page);
        $this->assertStringContainsString('openNewTabPicker', $page);
        $this->assertStringContainsString('startMoveOrderLine', $page);
        $this->assertStringContainsString('selectWorkingTab(group.tabId)', $page);
        $this->assertStringContainsString('openOrderLine(line)', $page);
        $this->assertStringContainsString('removeEditingItem', $page);
        $this->assertStringContainsString('changeEditingItemTab', $page);
        $this->assertStringContainsString('newTabColumnSpan', $page);
        $this->assertStringContainsString('data-send-order', $page);
        $this->assertStringContainsString('data-shrink-summary', $page);
        $this->assertStringContainsString('data-expand-summary', $page);
        $this->assertStringContainsString('visibleOrderSummaryGroups', $page);
        $this->assertStringContainsString("__('Send order')", $page);
        $this->assertStringContainsString('data-live-side-title', $page);
        $this->assertStringContainsString("__('New\\nAttendance')", $page);
        $this->assertStringContainsString('whitespace-pre-line', $page);
        $this->assertStringContainsString('text-ocean-deep/[0.08]', $page);
        $this->assertStringContainsString('<template v-if="preview" #header>', $page);
        $this->assertStringNotContainsString('data-live-start-actions', $page);
        $this->assertStringNotContainsString('data-start-order', $page);
        $this->assertStringContainsString('startNew', $page);
        $this->assertStringContainsString('applyStartMode', $page);
        $this->assertStringContainsString('resetWorkingContext', $page);
        $this->assertStringContainsString('promptOrderContext', $page);
        $this->assertStringContainsString('startChannelFor', $page);
        $this->assertStringContainsString("props.startMode !== 'order'", $page);
        $this->assertStringNotContainsString("applyStartMode(props.startMode || 'order')", $page);
        $this->assertStringContainsString("setPicker('location', true)", $page);
        $this->assertStringContainsString("openTabPicker('switch')", $page);
        $this->assertStringContainsString('clearDraftCarts', $page);
        $this->assertStringContainsString('cartsByContext.value = {}', $page);
        $this->assertStringContainsString('clearDraftCarts();', $page);
        $this->assertStringContainsString("selectedTabId.value = ''", $page);
        $this->assertStringContainsString("selectedLocationId.value = ''", $page);
        $this->assertStringContainsString('!group.isActive && group.lines.length > 0', $page);
        $this->assertStringContainsString('applyAttendanceContext', $page);
        $this->assertStringContainsString('locationIdMatchingValue', $page);
        $this->assertStringContainsString('applyAttendanceContext(props.attendance)', $page);
        $this->assertStringContainsString("route('attendances.take-order')", $page);
        $this->assertStringContainsString('pendingOrderGroups', $page);
        $this->assertStringContainsString('sendOrderPayloads', $page);
        $this->assertStringContainsString('sendOrderPayloadFor', $page);
        $this->assertStringContainsString('group.lines.length > 0', $page);
        $this->assertStringContainsString('preserveState: true', $page);
        $this->assertStringNotContainsString("__('New Order')", $page);
        $this->assertStringNotContainsString("__('New Table')", $page);
        $this->assertStringNotContainsString("__('New Tab')", $page);
        $this->assertStringContainsString('data-order-success', $page);
        $this->assertStringContainsString('data-order-success-ok', $page);
        $this->assertStringContainsString('data-order-success-table', $page);
        $this->assertStringContainsString('data-order-success-tab', $page);
        $this->assertStringContainsString('orderSuccessTable', $page);
        $this->assertStringContainsString('orderSuccessTab', $page);
        $this->assertStringContainsString("__('Table')", $page);
        $this->assertStringContainsString("__('Tab')", $page);
        $this->assertStringContainsString("__('placed successfully')", $page);
        $this->assertStringContainsString("__('OK')", $page);
        $this->assertStringContainsString('closeOrderSuccess', $page);
        $this->assertStringContainsString("__('Shrink summary')", $page);
        $this->assertStringContainsString("__('Expand summary')", $page);
        $this->assertStringContainsString('data-compact-summary', $page);
        $this->assertStringContainsString('compactOrderSummary', $page);
        $this->assertStringContainsString('rotate-90', $page);
        $this->assertStringContainsString('-rotate-90', $page);
        $this->assertStringContainsString("__('Current tab: :tab Qty items: :count items'", $page);
        $this->assertStringContainsString('can-remove', $page);
        $this->assertStringContainsString(':tab-items="groupedTabPickerItems"', $page);
        $this->assertStringContainsString(':tab-locations="tabLocationById"', $page);
        $this->assertStringContainsString('changeEditingItemTab(tabId, locationId', $page);
        $this->assertStringContainsString('moveOrderLineToTab', $page);
        $this->assertStringContainsString('groupedTabPickerItems', $page);
        $this->assertStringContainsString('warnIfTabOnOtherLocation', $page);
        $this->assertStringContainsString('data-new-tab-legend', $page);
        $this->assertStringContainsString('bg-emerald-600', $page);
        $this->assertStringContainsString("__('New tab')", $page);
        $this->assertStringContainsString("__('New tabs')", $page);
        $this->assertStringContainsString("__('Move to tab')", $page);
        $this->assertStringContainsString('locationSummaryVisible', $page);
        $this->assertStringContainsString('requirementColumnsClass', $page);
        $this->assertStringContainsString('data-order-summary', $page);
        $this->assertStringContainsString('min-w-0 w-full', $page);
        $this->assertStringContainsString('flex-col items-start', $page);
        $this->assertStringContainsString("__('Order')", $page);
        $this->assertStringContainsString('text-[10px] font-normal leading-none text-muted-foreground', $page);
        $this->assertStringContainsString('text-xl font-bold leading-none tracking-tight', $page);
        $this->assertStringContainsString('data-tab-value', $page);
        $this->assertStringContainsString('data-location-value', $page);
        $this->assertStringContainsString('needsTab', $page);
        $this->assertStringContainsString('usesServiceLocation', $page);
        $this->assertStringContainsString('needsLocation', $page);
        $this->assertStringContainsString('channelLocations.value.length', $page);
        $this->assertStringContainsString('needsIdentifier', $page);
        $this->assertStringContainsString('data-order-section', $page);
        $this->assertStringContainsString('data-customer-section', $page);
        $this->assertStringContainsString('data-customer-fields', $page);
        $this->assertStringContainsString('data-customer-name', $page);
        $this->assertStringContainsString('data-customer-phone', $page);
        $this->assertStringContainsString('needsDeliveryAddress', $page);
        $this->assertStringContainsString('neighborhoodOptions', $page);
        $this->assertStringContainsString('onNeighborhoodRegistered', $page);
        $this->assertStringContainsString('<DeliveryAddressFields', $page);
        $this->assertStringContainsString("route('attendances.customers.lookup')", $page);
        $this->assertStringContainsString('data-delivery-address-line', $page);
        $this->assertStringContainsString("__('Open in maps')", $page);
        $this->assertStringContainsString('NumberScrollPicker', $page);
        $this->assertStringContainsString('compact', $page);
        $this->assertStringContainsString("__('Tab')", $page);
        $this->assertStringContainsString("__('Consumption location')", $page);
        $this->assertStringContainsString('locationPickerLabel', $page);
        $this->assertStringContainsString('compareLocationsByNumber', $page);
        $this->assertStringContainsString('locationNumericSuffix', $page);
        $this->assertStringContainsString('numeric: true', $page);
        $this->assertStringNotContainsString('locationChipLabel', $page);
        $this->assertStringNotContainsString("__('Customer identifier')", $page);
        $this->assertStringContainsString("__('Name')", $page);
        $this->assertStringContainsString("__('Phone with area code')", $page);
        $this->assertStringContainsString("__('Enter the ZIP code')", $page);
        $this->assertStringContainsString("__('Enter the street')", $page);
        $this->assertStringContainsString("__('Enter the number')", $page);
        $this->assertStringContainsString("__('Choose a neighborhood')", $page);
        $this->assertStringContainsString("__('Enter the name')", $page);
        $this->assertStringContainsString("__('Enter the phone with area code')", $page);
        $this->assertStringContainsString('<ProductDetailDrawer', $page);
        $this->assertStringContainsString('data-order-summary', $page);
        $this->assertStringContainsString('orderSummaryLines', $page);
        $this->assertStringContainsString('cartsByContext', $page);
        $this->assertStringContainsString('currentCartKey', $page);
        $this->assertStringContainsString('tabsOnCurrentLocation', $page);
        $this->assertStringContainsString('ensureIndependentTabCart', $page);
        $this->assertStringContainsString('selectWorkingTab', $page);
        $this->assertStringContainsString('cartKeyFor', $page);
        $this->assertStringNotContainsString('<CartPanel', $page);
        $this->assertStringContainsString("v-bind=\"preview ? { venue, previewWatermark: __('Attendant version') } : {}\"", $page);
        $this->assertStringContainsString('<template v-if="preview" #headerTitle>{{ __(\'Attendant version\') }}</template>', $page);
        $this->assertStringContainsString("__('Attendant version')", $page);
        $this->assertStringContainsString("__('Adaptive screen for phone, tablet or computer')", $page);
        $this->assertStringContainsString("route('menu.preview.customer.delivery')", $page);
        $this->assertStringContainsString("route('menu.index')", $page);
        $this->assertStringContainsString("__('Back')", $page);
        $this->assertStringNotContainsString("__('View order')", $page);
        $this->assertStringNotContainsString('fixed bottom-24 left-1/2 z-50', $page);
        $this->assertStringNotContainsString('border-4 border-warm-gold', $page);
        $this->assertStringContainsString('catalogCategories', $page);
        $this->assertStringContainsString("id: 'combos'", $page);
        $this->assertStringContainsString('<GuestMenuCatalog', $delivery);
        $this->assertStringContainsString('mx-auto w-full max-w-lg', $page);
        $this->assertStringNotContainsString('sm:grid-cols-3', $page);
        $this->assertStringNotContainsString('grid grid-cols-2 gap-3', $page);
        $this->assertStringNotContainsString('Preview — attendant view', $page);
        $this->assertStringNotContainsString('Back to menu', $page);
        $this->assertStringNotContainsString('<DeliveryCheckoutPanel', $page);
    }

    public function test_portuguese_attendant_preview_copy_matches_the_delivery_preview(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/PreviewAttendant.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Versão Atendente', $translations['Attendant version']);
        $this->assertSame('Versão cliente - Delivery', $translations['Customer version - Delivery']);
        $this->assertSame('Tela adaptável para celular, tablet ou computador', $translations['Adaptive screen for phone, tablet or computer']);
        $this->assertSame('Voltar', $translations['Back']);
        $this->assertSame('Este estabelecimento ainda não tem itens no cardápio.', $translations['This venue has no menu items yet.']);
        $this->assertSame('Canal', $translations['Channel']);
        $this->assertSame('Novo pedido', $translations['New order']);
        $this->assertSame('Verificar status', $translations['Check status']);
        $this->assertSame('Comanda', $translations['Tab']);
        $this->assertSame('Nova comanda', $translations['New tab']);
        $this->assertSame('Novas comandas', $translations['New tabs']);
        $this->assertSame('Mover para comanda', $translations['Move to tab']);
        $this->assertSame(
            'Esta comanda já está cadastrada em :location. Você ainda pode lançar o pedido.',
            $translations['This tab is already registered at :location. You can still take the order.'],
        );
        $this->assertSame('Pedido', $translations['Order']);
        $this->assertSame('Local de consumo', $translations['Consumption location']);
        $this->assertSame('Nome', $translations['Name']);
        $this->assertSame('Telefone com DDD', $translations['Phone with area code']);
        $this->assertSame('CEP', $translations['ZIP code']);
        $this->assertSame('Rua', $translations['Street']);
        $this->assertSame('Número', $translations['Number']);
        $this->assertSame('Bairro', $translations['Neighborhood']);
        $this->assertSame('CEP não encontrado, preencha manualmente', $translations['ZIP code not found, fill in manually']);
        $this->assertSame('Bairro não atendido ou não cadastrado', $translations['Neighborhood not served or not registered']);
        $this->assertSame('Usar novo endereço', $translations['Use a new address']);
        $this->assertSame('Dados de entrega pendente', $translations['Delivery data pending']);
        $this->assertSame('Dados ok', $translations['Data OK']);
        $this->assertSame('Abrir no mapa', $translations['Open in maps']);
        $this->assertSame('Informe o nome', $translations['Enter the name']);
        $this->assertSame('Informe o telefone com DDD', $translations['Enter the phone with area code']);
        $this->assertSame('Combos', $translations['Combos']);
        $this->assertSame('Enviar pedido', $translations['Send order']);
        $this->assertSame('Diminuir resumo', $translations['Shrink summary']);
        $this->assertSame('Ampliar resumo', $translations['Expand summary']);
        $this->assertSame(
            'comanda atual: :tab qtde itens: :count itens',
            $translations['Current tab: :tab Qty items: :count items'],
        );
        $this->assertSame('Adicionar local de consumo', $translations['Add consumption location']);
        $this->assertSame('Novo Atendimento', $translations['New Attendance']);
        $this->assertSame("Novo\nAtendimento", $translations["New\nAttendance"]);
        $this->assertSame('Novo Pedido', $translations['New Order']);
        $this->assertSame('Nova mesa', $translations['New Table']);
        $this->assertSame('Nova comanda', $translations['New Tab']);
        $this->assertSame('Pedido enviado', $translations['Order sent']);
        $this->assertSame('Pedido de :name', $translations['Order for :name']);
        $this->assertSame('Mesa', $translations['Table']);
        $this->assertSame('Comanda', $translations['Tab']);
        $this->assertSame('realizado com sucesso', $translations['placed successfully']);
        $this->assertSame('OK', $translations['OK']);
        $this->assertSame('Digite a comanda', $translations['Type the tab']);
        $this->assertSame('Digite a mesa', $translations['Type the table']);
        $this->assertSame('Digite o identificador do cliente', $translations['Type the customer identifier']);
        $this->assertSame('Entregue', $translations['Delivered']);
        $this->assertSame('Não entregue', $translations['Not delivered']);
        $this->assertSame('Com pedidos', $translations['With orders']);
        $this->assertSame('Sem pedidos', $translations['Without orders']);
        $this->assertSame(
            'Digite a comanda ou a mesa para ver o status.',
            $translations['Type the tab or the table to see the status.'],
        );
    }
}
