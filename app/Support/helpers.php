<?php

use App\Services\SettingsService;

if (! function_exists('money')) {
    /**
     * Format an amount as Indian rupees with lakh/crore grouping: ₹1,23,456.00
     */
    function money(string|int|float|null $amount, bool $symbol = true): string
    {
        $amount = $amount === null ? '0' : (string) $amount;
        $negative = str_starts_with($amount, '-');
        $amount = ltrim($amount, '-');
        $fixed = number_format((float) $amount, 2, '.', '');
        [$whole, $fraction] = explode('.', $fixed);

        $grouped = indian_number($whole);

        return ($negative ? '-' : '').($symbol ? '₹' : '').$grouped.'.'.$fraction;
    }
}

if (! function_exists('indian_number')) {
    /**
     * Group an integer string using the Indian numbering system (12,34,567).
     */
    function indian_number(string|int $whole): string
    {
        $whole = (string) $whole;
        if (strlen($whole) <= 3) {
            return $whole;
        }

        $last3 = substr($whole, -3);
        $rest = substr($whole, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);

        return $rest.','.$last3;
    }
}

if (! function_exists('qty')) {
    /**
     * Format a quantity, trimming insignificant zeros (2.500 → 2.5, 3.000 → 3).
     */
    function qty(string|int|float|null $quantity): string
    {
        $q = number_format((float) ($quantity ?? 0), 3, '.', '');
        $q = rtrim(rtrim($q, '0'), '.');

        return $q === '' || $q === '-0' ? '0' : $q;
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}
