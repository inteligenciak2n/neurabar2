<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class GuestHubPinRemovedTest extends TestCase
{
    public function test_guest_hub_does_not_ask_for_an_access_pin(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Guest/Hub.vue');

        $this->assertNotFalse($page);
        $this->assertStringNotContainsString('pin-setup', $page);
        $this->assertStringNotContainsString("__('Create your PIN')", $page);
        $this->assertStringNotContainsString("__('Enter your PIN to view your orders.')", $page);
        $this->assertStringNotContainsString('params: { pin:', $page);
        $this->assertStringContainsString('const view = ref(\'hub\')', $page);
        $this->assertStringContainsString('axios.post(`/g/${props.token}/session`, {})', $page);
        $this->assertStringContainsString('axios.get(`/g/${props.token}/orders`)', $page);
    }
}
