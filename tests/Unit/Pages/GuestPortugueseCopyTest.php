<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class GuestPortugueseCopyTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function portuguese(string $file): array
    {
        $path = dirname(__DIR__, 3).'/resources/translations/pt/'.$file;

        $this->assertFileExists($path);

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_product_drawer_uses_portuguese_option_labels(): void
    {
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/ProductDetailDrawer.vue');
        $translations = $this->portuguese('ProductDetailDrawer.json');

        $this->assertNotFalse($component);
        $this->assertStringContainsString('class="mx-auto h-64 w-full object-cover"', $component);
        $this->assertStringContainsString('mb-5 text-center', $component);
        $this->assertStringContainsString('-mt-8 rounded-t-3xl pt-6', $component);
        $this->assertStringNotContainsString('size-16 shrink-0 overflow-hidden rounded-xl bg-muted', $component);
        $this->assertStringContainsString("__('Choose an option')", $component);
        $this->assertStringContainsString("__('Default')", $component);
        $this->assertStringContainsString("__('Variations')", $component);
        $this->assertStringContainsString("const selectedVariationId = ref('');", $component);
        $this->assertStringContainsString(":value=\"''\"", $component);
        $this->assertStringContainsString('Number(product.price).toFixed(2)', $component);
        $this->assertStringContainsString('v-if="product.variations?.length"', $component);
        $this->assertStringContainsString("__('Choose')", $component);
        $this->assertStringContainsString('group.multiple_selection', $component);
        $this->assertStringContainsString('toggleOption(group, option.id)', $component);
        $this->assertStringContainsString('{ immediate: true }', $component);
        $this->assertStringNotContainsString('min_selections', $component);
        $this->assertStringNotContainsString('max_selections', $component);
        $this->assertStringContainsString("__('optional')", $component);
        $this->assertSame('Escolha uma opção', $translations['Choose an option']);
        $this->assertSame('Padrão', $translations['Default']);
        $this->assertSame('Variações', $translations['Variations']);
        $this->assertSame('Escolha', $translations['Choose']);
        $this->assertSame('Escolha até', $translations['Choose up to']);
        $this->assertSame('Adicionar', $translations['Add']);
        $this->assertStringContainsString("__('Observations')", $component);
        $this->assertStringContainsString("__('Comments about the dish or the order.')", $component);
        $this->assertStringContainsString('editingItem', $component);
        $this->assertStringContainsString('z-[60]', $component);
        $this->assertStringNotContainsString('v-if="!preview"', $component);
        $this->assertSame('Observações', $translations['Observations']);
        $this->assertSame('Comentários sobre o prato ou o pedido.', $translations['Comments about the dish or the order.']);
        $this->assertSame('Atualizar item', $translations['Update item']);
        $this->assertStringContainsString("__('Reviewing order')", $component);
        $this->assertSame('Conferindo pedido', $translations['Reviewing order']);
        $this->assertStringContainsString("__('Update item')", $component);
        $this->assertStringContainsString("__('Send order')", $component);
        $this->assertSame('Enviar pedido', $translations['Send order']);
        $this->assertStringContainsString("__('Quantity:')", $component);
        $this->assertSame('Quantidade:', $translations['Quantity:']);
        $this->assertStringContainsString('@click="quantity--"', $component);
        $this->assertStringContainsString('@click="quantity++"', $component);
        $this->assertStringContainsString('{{ quantity }}', $component);
        $this->assertStringContainsString('servingsText', $component);
        $this->assertSame('Serve 1 pessoa', $translations['Serves 1 person']);
        $this->assertSame('Serve :count pessoas', $translations['Serves :count people']);
    }

    public function test_cart_panel_translates_the_order_error(): void
    {
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/CartPanel.vue');
        $translations = $this->portuguese('CartPanel.json');

        $this->assertNotFalse($component);
        $this->assertStringContainsString("__('Error placing order.')", $component);
        $this->assertStringNotContainsString("?? 'Error placing order.'", $component);
        $this->assertSame('Erro ao enviar o pedido.', $translations['Error placing order.']);
        $this->assertSame('Faça o pedido', $translations['Place Order']);
        $this->assertSame('Esta é uma prévia. Pedidos não podem ser feitos daqui.', $translations['This is a preview. Orders cannot be placed from here.']);
        $this->assertStringContainsString('preview || !items.length', $component);
    }

    public function test_delivery_checkout_and_hub_copy_are_portuguese(): void
    {
        $checkout = $this->portuguese('DeliveryCheckoutPanel.json');
        $hub = $this->portuguese('Hub.json');
        $track = $this->portuguese('TrackOrder.json');

        $this->assertSame('Seu pedido', $checkout['Your order']);
        $this->assertSame('O pedido', $checkout['The order']);
        $this->assertSame(':count item', $checkout[':count item']);
        $this->assertSame(':count itens', $checkout[':count items']);
        $this->assertSame('Total', $checkout['Total']);
        $this->assertSame('Local entrega', $checkout['Delivery location']);
        $this->assertSame('Pagamento', $checkout['Payment']);
        $this->assertSame('Avançar', $checkout['Advance']);
        $this->assertSame('Pedido correto, avançar', $checkout['Order correct, advance']);
        $this->assertSame('Confirme o pedido para avançar', $checkout['Confirm the order to continue']);
        $this->assertSame('Entrega', $checkout['Delivery']);
        $this->assertSame('Retirada', $checkout['Pickup']);
        $this->assertSame('Nome completo', $checkout['Full name']);
        $this->assertSame('Ponto de referência', $checkout['Reference point']);
        $this->assertSame('Comentário na hora da entrega', $checkout['Comment at delivery']);
        $this->assertSame('Deixar na recepção', $checkout['Leave at the reception']);
        $this->assertSame('Comentário na retirada', $checkout['Comment at pickup']);
        $this->assertSame('Retirar no balcão', $checkout['Pick up at the counter']);
        $this->assertSame('Fazer pedido', $checkout['Place Order']);
        $this->assertSame('Enviar pedido', $checkout['Send order']);
        $this->assertSame(
            'Esta é uma prévia. Pedidos não podem ser feitos daqui.',
            $checkout['This is a preview. Orders cannot be placed from here.'],
        );
        $this->assertSame('Dinheiro', $checkout['Cash']);
        $this->assertSame('Cartão', $checkout['Card']);
        $this->assertSame('Pix antecipado', $checkout['Advance PIX']);
        $this->assertSame('Código do pedido', $checkout['Order code']);
        $this->assertSame('Modo de pagamento: No momento da entrega', $checkout['Payment method: At delivery time']);
        $this->assertSame('Cartão de crédito', $checkout['Credit Card']);
        $this->assertSame('Cartão de débito', $checkout['Debit Card']);
        $this->assertSame('Pix', $checkout['Pix']);
        $this->assertSame('Outros', $checkout['Other']);
        $this->assertSame('Entrega ou retirada?', $hub['Delivery or Takeaway?']);
        $this->assertSame('O PIN deve ter 4 dígitos.', $hub['PIN must be 4 digits.']);
        $this->assertSame('Localização obrigatória', $hub['Location required']);
        $this->assertSame('Código do pedido', $track['Order code']);
        $this->assertSame('Acompanhar pedido', $track['Track Order']);
        $this->assertSame('Pedido recebido', $track['Order received']);
        $this->assertSame('Saiu para entrega', $track['Out for delivery']);

        $trackPage = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/TrackOrder.vue');
        $this->assertStringContainsString("__('Order code')", $trackPage);
        $this->assertStringContainsString('order.code ?? order.order_number', $trackPage);
    }

    public function test_finance_and_period_filter_copy_are_portuguese(): void
    {
        $index = $this->portuguese('Index.json');
        $period = $this->portuguese('PeriodFilter.json');
        $stat = $this->portuguese('StatCard.json');
        $products = $this->portuguese('Products.json');

        $this->assertSame('Cartão de crédito', $index['Credit card']);
        $this->assertSame('Faturamento bruto', $index['Gross revenue']);
        $this->assertSame('Formas de pagamento', $index['Payment methods']);
        $this->assertSame('Este estabelecimento', $index['This venue']);
        $this->assertSame('Atualize e gerencie', $index['Update and manage']);
        $this->assertSame('seu cardápio aqui', $index['your menu here']);
        $this->assertSame('Hoje', $period['Today']);
        $this->assertSame('Este mês', $period['This month']);
        $this->assertSame('vs período anterior', $stat['vs previous period']);
        $this->assertSame('Inativo', $products['Inactive']);
        $this->assertSame('Ativar', $products['Activate']);
        $catalog = $this->portuguese('GuestMenuCatalog.json');
        $this->assertSame('Valor:', $catalog['Price:']);
        $this->assertSame('Cardápio indisponível', $catalog['Menu not available']);
        $this->assertSame('Serve 1 pessoa', $catalog['Serves 1 person']);
        $this->assertSame('Serve :count pessoas', $catalog['Serves :count people']);

        $catalogPage = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/GuestMenuCatalog.vue');
        $this->assertStringContainsString('servingsText(product)', $catalogPage);
        $this->assertStringContainsString('min-w-0 truncate text-[10px] leading-tight', $catalogPage);
        $this->assertStringContainsString('mt-auto flex items-baseline justify-between gap-2', $catalogPage);
    }
}
