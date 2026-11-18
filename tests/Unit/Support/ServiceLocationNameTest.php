<?php

namespace Tests\Unit\Support;

use App\Support\ServiceLocationName;
use PHPUnit\Framework\TestCase;

class ServiceLocationNameTest extends TestCase
{
    public function test_compose_joins_letter_prefix_with_a_space(): void
    {
        $this->assertSame('Mesa 1', ServiceLocationName::compose('Mesa', 1));
        $this->assertSame('15', ServiceLocationName::compose('', 15));
    }

    public function test_compose_inserts_a_hyphen_when_the_prefix_ends_with_a_digit(): void
    {
        $this->assertSame('Quiosque3-1', ServiceLocationName::compose('Quiosque3', 1));
    }
}
