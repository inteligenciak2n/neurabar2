<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_from_float_converts_currency_units_to_cents(): void
    {
        $this->assertSame(9990, Money::fromFloat(99.90));
        $this->assertSame(9990, Money::fromFloat('99.90'));
        $this->assertSame(10000, Money::fromFloat(100));
    }

    public function test_from_float_treats_empty_values_as_zero(): void
    {
        $this->assertSame(0, Money::fromFloat(null));
        $this->assertSame(0, Money::fromFloat(''));
    }

    public function test_from_float_rounds_sub_cent_input(): void
    {
        $this->assertSame(1, Money::fromFloat(0.005));
        $this->assertSame(0, Money::fromFloat(0.004));
    }

    public function test_from_float_accepts_the_micro_scale(): void
    {
        $this->assertSame(500, Money::fromFloat(0.05, Money::MICRO_SCALE));
    }

    public function test_to_float_converts_back_to_currency_units(): void
    {
        $this->assertSame(99.9, Money::toFloat(9990));
        $this->assertSame(0.0, Money::toFloat(null));
        $this->assertSame(0.05, Money::toFloat(500, Money::MICRO_SCALE));
    }

    public function test_from_micros_converts_unit_prices_to_cents(): void
    {
        // 500 centésimos de centavo = R$ 0,05 = 5 centavos.
        $this->assertSame(5, Money::fromMicros(500));
        $this->assertSame(2000, Money::fromMicros(200_000));
    }

    public function test_multiply_applies_a_fractional_factor(): void
    {
        // Proration de 15 dos 31 dias de julho sobre R$ 31,00.
        $this->assertSame(1500, Money::multiply(3100, 15 / 31));
        $this->assertSame(0, Money::multiply(3100, 0.0));
    }

    public function test_percentage_uses_basis_points(): void
    {
        // 15% de R$ 200,00.
        $this->assertSame(3000, Money::percentage(20000, 1500));
        $this->assertSame(0, Money::percentage(20000, 0));
    }

    public function test_format_renders_brazilian_currency(): void
    {
        $this->assertSame('R$ 1.299,90', Money::format(129990));
        $this->assertSame('R$ 0,00', Money::format(null));
        $this->assertSame('R$ 0,0500', Money::format(500, Money::MICRO_SCALE));
    }
}
