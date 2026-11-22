<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_indian_formatting(): void
    {
        $this->assertSame('₹0.00', money(0));
        $this->assertSame('₹999.00', money('999'));
        $this->assertSame('₹1,000.00', money('1000'));
        $this->assertSame('₹12,34,567.50', money('1234567.5'));
        $this->assertSame('₹1,00,00,000.00', money('10000000'));
        $this->assertSame('-₹1,234.00', money('-1234'));
        $this->assertSame('1,234.00', money('1234', false));
    }

    public function test_quantity_formatting(): void
    {
        $this->assertSame('3', qty('3.000'));
        $this->assertSame('2.5', qty('2.500'));
        $this->assertSame('0', qty(null));
    }

    public function test_multiplication_rounds_half_up_to_two_decimals(): void
    {
        $this->assertSame('7.50', Money::mul('3', '2.50'));
        $this->assertSame('0.03', Money::mul('3', '0.0083'));
        $this->assertSame('1.01', Money::mul('1.005', '1'));
        $this->assertSame('33.33', Money::mul('3.333', '10'));
    }

    public function test_percent_and_arithmetic(): void
    {
        $this->assertSame('5.00', Money::percent('100', '5'));
        $this->assertSame('18.90', Money::percent('105', '18'));
        $this->assertSame('0.38', Money::percent('7.5', '5'));
        $this->assertSame('12.34', Money::add('10', '2.34'));
        $this->assertSame('-2.34', Money::sub('10', '12.34'));
        $this->assertSame(1, Money::cmp('10.01', '10'));
        $this->assertTrue(Money::isZero('0.00'));
    }
}
