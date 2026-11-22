<?php

namespace App\Support;

/**
 * Decimal money arithmetic on strings using bcmath. All results have 2 decimals.
 */
final class Money
{
    public static function of(string|int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }

    public static function add(string|int|float $a, string|int|float $b): string
    {
        return bcadd(self::of($a), self::of($b), 2);
    }

    public static function sub(string|int|float $a, string|int|float $b): string
    {
        return bcsub(self::of($a), self::of($b), 2);
    }

    /** qty × price, rounded half-up to 2 decimals. */
    public static function mul(string|int|float $quantity, string|int|float $price): string
    {
        $raw = bcmul(number_format((float) $quantity, 3, '.', ''), self::of($price), 6);

        return self::round($raw);
    }

    /** amount × percent / 100. */
    public static function percent(string|int|float $amount, string|int|float $percent): string
    {
        $raw = bcdiv(bcmul(self::of($amount), number_format((float) $percent, 4, '.', ''), 8), '100', 8);

        return self::round($raw);
    }

    public static function round(string $value): string
    {
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');
        $rounded = bcadd($value, '0.005', 3);
        $rounded = bcdiv($rounded, '1', 2);

        return ($negative && bccomp($rounded, '0', 2) !== 0 ? '-' : '').$rounded;
    }

    public static function cmp(string|int|float $a, string|int|float $b): int
    {
        return bccomp(self::of($a), self::of($b), 2);
    }

    public static function max(string|int|float $a, string|int|float $b): string
    {
        return self::cmp($a, $b) >= 0 ? self::of($a) : self::of($b);
    }

    public static function isZero(string|int|float $a): bool
    {
        return self::cmp($a, 0) === 0;
    }
}
