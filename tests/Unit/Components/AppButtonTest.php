<?php

namespace Tests\Unit\Components;

use PHPUnit\Framework\TestCase;

class AppButtonTest extends TestCase
{
    public function test_destructive_variant_uses_a_matte_opaque_red(): void
    {
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/AppButton.vue');

        $this->assertNotFalse($component);
        $this->assertStringContainsString("destructive: 'bg-[#c45c5c] text-white hover:bg-[#ad4f4f] focus-visible:ring-[#c45c5c]'", $component);
        $this->assertStringNotContainsString("destructive: 'bg-destructive text-destructive-foreground hover:bg-red-600", $component);
    }

    public function test_accent_variant_uses_the_brand_gold(): void
    {
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/AppButton.vue');

        $this->assertNotFalse($component);
        $this->assertStringContainsString("['primary', 'secondary', 'accent', 'success', 'destructive', 'ghost']", $component);
        $this->assertStringContainsString("accent:      'bg-warm-gold text-white hover:bg-[#8c7354] focus-visible:ring-warm-gold'", $component);
        $this->assertStringContainsString("success:     'bg-[#5c9a6c] text-white hover:bg-[#4e7d5a] focus-visible:ring-[#5c9a6c]'", $component);
    }
}
