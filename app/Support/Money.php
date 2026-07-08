<?php

namespace App\Support;

class Money
{
    public static function toInteger(float|int|string $amount): int
    {
        return (int) round((float) $amount);
    }

    public static function minimumAmount(): int
    {
        return (int) config('payments.min_stk_amount', 10);
    }

    public static function isAtLeastMinimum(float|int|string $amount): bool
    {
        return self::toInteger($amount) >= self::minimumAmount();
    }
}
