<?php

namespace Tests\Unit\Support;

use App\Support\TabCode;
use PHPUnit\Framework\TestCase;

class TabCodeTest extends TestCase
{
    public function test_normalize_prefix_keeps_letters_and_digits(): void
    {
        $this->assertSame('JIOSQUE3', TabCode::normalizePrefix('jiosque3-'));
        $this->assertSame('CASA1', TabCode::normalizePrefix('casa1'));
        $this->assertSame('BAR', TabCode::normalizePrefix('Bar!'));
    }

    public function test_letter_prefix_concatenates_the_number(): void
    {
        $this->assertSame('A1', TabCode::compose('a', 1));
        $this->assertSame('BAR100', TabCode::compose('bar', 100));
        $this->assertSame('15', TabCode::compose('', 15));
    }

    public function test_prefix_ending_with_a_digit_inserts_a_hyphen(): void
    {
        $this->assertSame('CASA1-1', TabCode::compose('casa1', 1));
        $this->assertSame('JIOSQUE3-100', TabCode::compose('jiosque3', 100));
        $this->assertSame('CASA1-1', TabCode::compose('casa1-', 1));
    }

    public function test_prefix_from_code_keeps_digits_when_a_hyphen_separates_the_number(): void
    {
        $this->assertSame('CASA1', TabCode::prefixFromCode('CASA1-1'));
        $this->assertSame('JIOSQUE3', TabCode::prefixFromCode('jiosque3-15'));
        $this->assertSame('A', TabCode::prefixFromCode('A1'));
        $this->assertSame('', TabCode::prefixFromCode('15'));
        $this->assertSame('VIP', TabCode::prefixFromCode('VIP'));
        $this->assertSame('CASA', TabCode::prefixFromCode('CASA1'));
    }
}
