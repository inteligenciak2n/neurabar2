<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class OrderTakerLayoutTest extends TestCase
{
    public function test_taker_stacks_the_menu_and_keeps_the_cart_reachable_on_a_phone(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Orders/Taker.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('grid-cols-2', $page);
        $this->assertStringContainsString('pb-28 lg:h-[calc(100vh-10rem)] lg:flex-row', $page);
        $this->assertStringContainsString('fixed inset-x-0 bottom-0', $page);
        $this->assertStringContainsString('cartOpen', $page);
        $this->assertStringContainsString('lg:w-72', $page);
        $this->assertStringContainsString('min-h-12', $page);
        $this->assertStringNotContainsString('flex h-[calc(100vh-10rem)] gap-4 overflow-hidden', $page);
        $this->assertStringNotContainsString('flex w-72 flex-shrink-0 flex-col', $page);
    }
}
