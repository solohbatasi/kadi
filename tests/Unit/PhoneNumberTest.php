<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_sanitize_removes_non_digits(): void
    {
        $this->assertSame('254712345678', PhoneNumber::sanitize('+254 712-345-678'));
    }

    public function test_normalize_keeps_254_prefixed_numbers(): void
    {
        $this->assertSame('254712345678', PhoneNumber::normalize('254712345678'));
    }

    public function test_normalize_converts_local_zero_prefixed_number(): void
    {
        $this->assertSame('254712345678', PhoneNumber::normalize('0712345678'));
    }

    public function test_normalize_converts_local_9_digit_number(): void
    {
        $this->assertSame('254712345678', PhoneNumber::normalize('712345678'));
    }
}
