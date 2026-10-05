<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class DeliveryCheckoutTabsTest extends TestCase
{
    public function test_checkout_panel_splits_order_location_and_payment_into_product_style_tabs(): void
    {
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/Delivery/DeliveryCheckoutPanel.vue');

        $this->assertNotFalse($component);
        $this->assertStringContainsString('role="tablist"', $component);
        $this->assertStringContainsString('role="tab"', $component);
        $this->assertStringContainsString('checkoutTabClass', $component);
        $this->assertStringContainsString('rounded-t-lg border-x border-t', $component);
        $this->assertStringContainsString("__('The order')", $component);
        $this->assertStringContainsString('orderTabSummary', $component);
        $this->assertStringContainsString('showOrderTabSummary', $component);
        $this->assertStringContainsString('showLocationTabSummary', $component);
        $this->assertStringContainsString('locationStreetLine', $component);
        $this->assertStringContainsString('locationKindLabel', $component);
        $this->assertStringContainsString("':count item'", $component);
        $this->assertStringContainsString("':count items'", $component);
        $this->assertStringContainsString("__('Total')", $component);
        $this->assertStringContainsString('formatReais', $component);
        $this->assertStringContainsString('flex-col items-center justify-center', $component);
        $this->assertStringContainsString('venue.street, venue.number', $component);
        $this->assertStringContainsString('address.value.street, address.value.number', $component);
        $this->assertStringContainsString("__('Delivery location')", $component);
        $this->assertStringContainsString("__('Payment')", $component);
        $this->assertStringContainsString("activeTab === 'order'", $component);
        $this->assertStringContainsString("activeTab === 'location'", $component);
        $this->assertStringContainsString("selectTab('payment')", $component);
        $this->assertStringContainsString("__('Order correct, advance')", $component);
        $this->assertStringContainsString("__('Advance')", $component);
        $this->assertStringContainsString('>>', $component);
        $this->assertStringContainsString('advanceFromOrder', $component);
        $this->assertStringContainsString('completedTabs.order', $component);
        $this->assertStringContainsString('isTabLocked', $component);
        $this->assertStringContainsString('tabLockMessage', $component);
        $this->assertStringContainsString("__('Confirm the order to continue')", $component);
        $this->assertStringContainsString('cursor-not-allowed', $component);
        $this->assertStringContainsString('M5 13l4 4L19 7', $component);
        $this->assertStringContainsString("emit('select-item', index)", $component);
        $this->assertStringContainsString("__('Send order')", $component);
        $this->assertStringContainsString('max-w-lg', $component);
        $this->assertStringContainsString('flex min-w-0 flex-1 flex-col items-center justify-center', $component);
        $this->assertStringContainsString("__('Comment at delivery')", $component);
        $this->assertStringContainsString("__('Comment at pickup')", $component);
        $this->assertStringContainsString("__('Pick up at the counter')", $component);
        $this->assertStringContainsString("fulfillmentType === 'pickup'", $component);
        $this->assertStringContainsString("fulfillmentType === 'delivery' ? 'bg-green-600 text-white' : 'bg-red-500 text-white'", $component);
        $this->assertStringContainsString("fulfillmentType === 'pickup' ? 'bg-green-600 text-white' : 'bg-red-500 text-white'", $component);
        $this->assertStringContainsString('M6 18L18 6M6 6l12 12', $component);
        $this->assertStringContainsString('venueAddress', $component);
        $this->assertStringContainsString('h-10 w-10', $component);
        $this->assertStringContainsString('venue?.name', $component);
        $this->assertStringContainsString('deliveryComment', $component);
        $this->assertStringContainsString('delivery_comment', $component);
        $this->assertStringContainsString('overflow-x-hidden overflow-y-auto', $component);
        $this->assertStringContainsString('[field-sizing:content]', $component);
        $this->assertStringContainsString('placeholder:text-xs placeholder:italic', $component);
        $this->assertStringNotContainsString('requiredPlaceholder', $component);
        $this->assertStringContainsString('requiredClass', $component);
        $this->assertStringContainsString('border-red-500', $component);
        $this->assertStringContainsString("empty ? 'border-red-500' : 'border-border'", $component);
        $this->assertStringNotContainsString('max-w-sm', $component);
        $this->assertStringContainsString("__('Total')", $component);
        $this->assertStringContainsString('itemsTotal.toFixed(2)', $component);
        $this->assertStringContainsString('v-if="activeTab === \'order\'" class="mb-3 flex items-center justify-between text-sm"', $component);
        $this->assertStringContainsString('preview || submitting || !canSubmit', $component);
        $this->assertStringContainsString("__('This is a preview. Orders cannot be placed from here.')", $component);
        $this->assertStringContainsString("name: 'DeliveryCheckoutPanel'", $component);
        $this->assertStringContainsString('allocateOrderCode', $component);
        $this->assertStringContainsString('orderCode', $component);
        $this->assertStringContainsString("__('Order code')", $component);
        $this->assertStringContainsString('checkoutPaymentOptions', $component);
        $this->assertStringContainsString('selectedPaymentTypes', $component);
        $this->assertStringContainsString("__('Payment method: At delivery time')", $component);
        $this->assertStringContainsString("__('Card')", $component);
        $this->assertStringContainsString("__('Advance PIX')", $component);
        $this->assertStringContainsString("__('Cash')", $component);
        $this->assertStringContainsString('type="checkbox"', $component);
        $this->assertStringContainsString('v-model="selectedPaymentTypes"', $component);
        $this->assertStringNotContainsString('setPaymentAmount', $component);
        $this->assertStringNotContainsString('paymentAmounts', $component);
        $this->assertStringNotContainsString("__('Remaining')", $component);
        $this->assertStringContainsString('code: orderCode.value ? Number(orderCode.value) : undefined', $component);
        $this->assertStringContainsString('selectedPaymentMethods.value', $component);
        $this->assertStringNotContainsString('type="radio"', $component);
        $this->assertStringNotContainsString("__('Split into another payment method')", $component);
        $this->assertStringContainsString('item.modifier_details', $component);
        $this->assertStringContainsString('Number(feeZone.value.fee ?? 0)', $component);
        $this->assertStringNotContainsString('<option v-for="m in acceptedPaymentMethods" :key="m" :value="m">{{ m }}</option>', $component);
        $this->assertStringNotContainsString("fulfillmentType.value === 'delivery' ? feeZone.value.fee : 0", $component);
        $this->assertStringNotContainsString('preview || !canGoToPayment || !canSubmit || submitting', $component);
        $this->assertStringNotContainsString('step === 1', $component);
        $this->assertStringNotContainsString('const step = ref(1)', $component);
    }
}
