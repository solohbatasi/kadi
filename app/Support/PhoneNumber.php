<?php

namespace App\Support;

class PhoneNumber
{
    public static function sanitize(string $value): string
    {
        return preg_replace('/[^0-9]/', '', $value) ?? '';
    }
}
