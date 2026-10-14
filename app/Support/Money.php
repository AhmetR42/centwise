<?php

namespace App\Support;

/**
 * Amounts are stored as whole cents to avoid floating point rounding.
 * The API accepts and returns them as decimal numbers (12.5 = 12,50).
 */
class Money
{
    public static function toCents(float|int|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public static function fromCents(?int $cents): ?float
    {
        return $cents === null ? null : round($cents / 100, 2);
    }
}
