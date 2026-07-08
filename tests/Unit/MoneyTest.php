<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_to_integer_converts_string_amount(): void
    {
        $this->assertSame(123, Money::toInteger('123'));
    }

    public function test_to_integer_rounds_decimal_amount(): void
    {
        $this->assertSame(124, Money::toInteger(123.6));
    }

    public function test_to_integer_handles_integer_amount(): void
    {
        $this->assertSame(100, Money::toInteger(100));
    }
}
