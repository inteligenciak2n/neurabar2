<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class TabCardCodeTest extends TestCase
{
    private function read(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        $this->assertNotFalse($contents);

        return $contents;
    }

    public function test_attendances_page_lets_staff_register_and_search_the_tab_card_code(): void
    {
        $page = $this->read('resources/js/Pages/Attendances/Index.vue');

        $this->assertStringContainsString("code: ''", $page);
        $this->assertStringContainsString('v-model="form.code"', $page);
        $this->assertStringContainsString('attendance.code', $page);
    }

    public function test_kitchen_screens_show_the_tab_card_code(): void
    {
        $this->assertStringContainsString('item.order.attendance.code', $this->read('resources/js/Pages/Kitchen/Kds.vue'));
        $this->assertStringContainsString('item.order.attendance.code', $this->read('resources/js/Pages/Kitchen/Monitor.vue'));
    }

    public function test_guest_cart_sends_the_tab_code_and_locks_it_after_the_first_order(): void
    {
        $cart = $this->read('resources/js/Components/Guest/CartPanel.vue');

        $this->assertStringContainsString('tab_code:', $cart);
        $this->assertStringContainsString('tabCodeLocked', $cart);
        $this->assertStringContainsString('maxlength="32"', $cart);
    }
}
