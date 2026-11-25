<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An exact amount of money, held as an integer number of minor units
 * (fils for AED, cents for USD). Never use floats for money.
 *
 * Money::of('19.99', 'AED')->multiply(3)->toDecimal(); // "59.97"
 */
final class Money implements JsonSerializable, Stringable
{
    /** Rates and percentages are handled with 8 decimal places of precision. */
    private const RATE_SCALE = 100_000_000;

    private function __construct(
        public readonly int $minor,
        public readonly string $currency,
        public readonly int $decimals = 2,
    ) {}

    public static function ofMinor(int $minor, string $currency, int $decimals = 2): self
    {
        return new self($minor, strtoupper($currency), $decimals);
    }

    /**
     * Create from a decimal value such as "149.50" (as stored in MySQL).
     */
    public static function of(string|int|float $amount, string $currency, int $decimals = 2): self
    {
        if (is_float($amount)) {
            // Only for convenience in tests/seeders; real values come from DECIMAL columns as strings.
            $amount = number_format($amount, $decimals, '.', '');
        }

        return new self(self::decimalToMinor((string) $amount, $decimals), strtoupper($currency), $decimals);
    }

    public static function zero(string $currency, int $decimals = 2): self
    {
        return new self(0, strtoupper($currency), $decimals);
    }

    public function toDecimal(): string
    {
        $negative = $this->minor < 0;
        $digits = (string) abs($this->minor);

        if ($this->decimals === 0) {
            return ($negative ? '-' : '').$digits;
        }

        $digits = str_pad($digits, $this->decimals + 1, '0', STR_PAD_LEFT);
        $whole = substr($digits, 0, -$this->decimals);
        $fraction = substr($digits, -$this->decimals);

        return ($negative ? '-' : '').$whole.'.'.$fraction;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minor + $other->minor, $this->currency, $this->decimals);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minor - $other->minor, $this->currency, $this->decimals);
    }

    public function multiply(int $quantity): self
    {
        return new self($this->minor * $quantity, $this->currency, $this->decimals);
    }

    /**
     * A percentage of this amount, e.g. ->percentage('5') for 5% VAT on a tax-exclusive price.
     */
    public function percentage(string|int $percent): self
    {
        $rate = self::decimalToScaled((string) $percent);

        return new self(self::roundDiv(self::multiplyExact($this->minor, $rate), 100 * self::RATE_SCALE), $this->currency, $this->decimals);
    }

    /**
     * The tax already included in this (tax-inclusive) amount.
     * 105.00 at 5% -> 5.00
     */
    public function includedTax(string|int $percent): self
    {
        $rate = self::decimalToScaled((string) $percent);

        return new self(
            self::roundDiv(self::multiplyExact($this->minor, $rate), 100 * self::RATE_SCALE + $rate),
            $this->currency,
            $this->decimals,
        );
    }

    /**
     * Convert to another currency. $rate = how many units of the target currency one unit of this currency buys.
     */
    public function convert(string $toCurrency, string|int $rate, int $toDecimals = 2): self
    {
        $scaledRate = self::decimalToScaled((string) $rate);
        $numerator = self::multiplyExact($this->minor, $scaledRate);
        $denominator = self::RATE_SCALE;

        $diff = $toDecimals - $this->decimals;
        if ($diff > 0) {
            $numerator = self::multiplyExact($numerator, 10 ** $diff);
        } elseif ($diff < 0) {
            $denominator *= 10 ** -$diff;
        }

        return new self(self::roundDiv($numerator, $denominator), strtoupper($toCurrency), $toDecimals);
    }

    public function isZero(): bool
    {
        return $this->minor === 0;
    }

    public function isNegative(): bool
    {
        return $this->minor < 0;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minor > $other->minor;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->minor === $other->minor;
    }

    public function jsonSerialize(): array
    {
        return ['amount' => $this->toDecimal(), 'currency' => $this->currency];
    }

    public function __toString(): string
    {
        return $this->toDecimal().' '.$this->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Cannot combine {$this->currency} with {$other->currency}.");
        }
    }

    private static function decimalToMinor(string $amount, int $decimals): int
    {
        $amount = trim($amount);

        if (! preg_match('/^(-)?(\d*)(?:\.(\d+))?$/', $amount, $m) || ($m[2] === '' && ($m[3] ?? '') === '')) {
            throw new InvalidArgumentException("Invalid money amount [{$amount}].");
        }

        $negative = $m[1] === '-';
        $whole = $m[2] === '' ? '0' : $m[2];
        $fraction = $m[3] ?? '';

        // Round half-up on any extra decimal digits.
        $kept = str_pad(substr($fraction, 0, $decimals), $decimals, '0');
        $minor = (int) ($whole.$kept);

        if (strlen($fraction) > $decimals && (int) $fraction[$decimals] >= 5) {
            $minor++;
        }

        return $negative ? -$minor : $minor;
    }

    private static function decimalToScaled(string $value): int
    {
        return self::decimalToMinor($value, 8);
    }

    /** Multiply two integers, refusing to silently overflow into a float. */
    private static function multiplyExact(int $a, int $b): int
    {
        $result = $a * $b;

        if (! is_int($result)) {
            throw new \OverflowException('Money calculation is too large to be exact.');
        }

        return $result;
    }

    /** Integer division rounded half away from zero. */
    private static function roundDiv(int $numerator, int $denominator): int
    {
        $quotient = intdiv($numerator, $denominator);
        $remainder = $numerator % $denominator;

        if (abs($remainder) * 2 >= abs($denominator)) {
            $quotient += ($numerator < 0) !== ($denominator < 0) ? -1 : 1;
        }

        return $quotient;
    }
}
