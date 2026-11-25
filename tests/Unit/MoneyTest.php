<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_keeps_exact_decimal_values(): void
    {
        $this->assertSame('19.99', Money::of('19.99', 'AED')->toDecimal());
        $this->assertSame(1999, Money::of('19.99', 'AED')->minor);
        $this->assertSame('0.05', Money::of('0.05', 'AED')->toDecimal());
        $this->assertSame('-3.50', Money::of('-3.5', 'AED')->toDecimal());
        $this->assertSame('1250.00', Money::of('1250', 'AED')->toDecimal());
    }

    public function test_floating_point_sums_are_exact(): void
    {
        // 0.1 + 0.2 is 0.30000000000000004 with floats.
        $total = Money::of('0.10', 'AED')->add(Money::of('0.20', 'AED'));

        $this->assertSame('0.30', $total->toDecimal());
    }

    public function test_multiply_and_subtract(): void
    {
        $line = Money::of('19.99', 'AED')->multiply(3);

        $this->assertSame('59.97', $line->toDecimal());
        $this->assertSame('49.97', $line->subtract(Money::of('10', 'AED'))->toDecimal());
    }

    #[DataProvider('inclusiveVat')]
    public function test_it_extracts_vat_from_inclusive_prices(string $price, string $expectedVat): void
    {
        $this->assertSame($expectedVat, Money::of($price, 'AED')->includedTax('5')->toDecimal());
    }

    public static function inclusiveVat(): array
    {
        return [
            ['105.00', '5.00'],
            ['99.99', '4.76'],
            ['1.00', '0.05'],
            ['0.00', '0.00'],
        ];
    }

    public function test_it_adds_vat_to_exclusive_prices(): void
    {
        $this->assertSame('5.00', Money::of('100', 'AED')->percentage('5')->toDecimal());
        $this->assertSame('0.63', Money::of('12.50', 'AED')->percentage('5')->toDecimal());
    }

    public function test_it_rounds_half_up(): void
    {
        $this->assertSame('1.01', Money::of('1.005', 'AED')->toDecimal());
        $this->assertSame('1.00', Money::of('1.004', 'AED')->toDecimal());
    }

    public function test_it_converts_between_currencies(): void
    {
        $this->assertSame('27.23', Money::of('100', 'AED')->convert('USD', '0.27229')->toDecimal());
        $this->assertSame('USD', Money::of('100', 'AED')->convert('USD', '0.27229')->currency);
        $this->assertSame('4010', Money::of('100', 'AED')->convert('JPY', '40.1', 0)->toDecimal());
        $this->assertSame('227000000.00', Money::of('10000000', 'AED')->convert('INR', '22.7')->toDecimal());
    }

    public function test_it_refuses_to_mix_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of('1', 'AED')->add(Money::of('1', 'USD'));
    }

    public function test_it_rejects_invalid_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of('12,50', 'AED');
    }
}
