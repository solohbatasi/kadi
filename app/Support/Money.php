<?php

namespace App\Support;

class Money
{
    public static function toInteger(float|int|string $amount): int
    {
        return (int) round((float) $amount);
    }
}
