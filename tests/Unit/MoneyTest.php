<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_decimal_amounts_become_whole_cents(): void
    {
        $this->assertSame(1235, Money::toCents(12.35));
        $this->assertSame(1235, Money::toCents('12.35'));
        $this->assertSame(10, Money::toCents(0.1));
        $this->assertSame(29, Money::toCents(0.29)); // 0.29 * 100 is 28.999... in floating point
    }

    public function test_cents_become_decimals(): void
    {
        $this->assertSame(12.35, Money::fromCents(1235));
        $this->assertSame(0.0, Money::fromCents(0));
        $this->assertNull(Money::fromCents(null));
    }
}
