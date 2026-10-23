<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class GuestCartLineItemsTest extends TestCase
{
    public function test_guest_menus_do_not_merge_the_same_product_with_different_modifiers(): void
    {
        $tableMenu = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/Menu.vue');
        $deliveryMenu = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/Delivery/Menu.vue');
        $drawer = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/ProductDetailDrawer.vue');
        $cart = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/CartPanel.vue');
        $checkout = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/Guest/Delivery/DeliveryCheckoutPanel.vue');

        $this->assertNotFalse($tableMenu);
        $this->assertNotFalse($deliveryMenu);
        $this->assertStringContainsString("import { mergeGuestCartItem } from '@/Utils/guestCart'", $tableMenu);
        $this->assertStringContainsString("import { mergeGuestCartItem } from '@/Utils/guestCart'", $deliveryMenu);
        $this->assertStringContainsString('mergeGuestCartItem(cartItems.value, item)', $tableMenu);
        $this->assertStringContainsString('mergeGuestCartItem(cartItems.value, item)', $deliveryMenu);
        $this->assertStringNotContainsString('i.product_id === item.product_id && i.variation_id === item.variation_id', $tableMenu);
        $this->assertStringNotContainsString('i.product_id === item.product_id && i.variation_id === item.variation_id', $deliveryMenu);

        $this->assertNotFalse($drawer);
        $this->assertStringContainsString('modifier_details: modifierDetails', $drawer);
        $this->assertStringContainsString('group: group.name', $drawer);
        $this->assertStringContainsString('name: option.name', $drawer);

        $this->assertNotFalse($cart);
        $this->assertStringContainsString('item.modifier_details', $cart);
        $this->assertStringContainsString('{{ detail.group }}: {{ detail.name }}', $cart);
        $this->assertStringContainsString('item.modifier_details', $checkout);
        $this->assertStringContainsString('{{ detail.group }}: {{ detail.name }}', $checkout);
    }
}
