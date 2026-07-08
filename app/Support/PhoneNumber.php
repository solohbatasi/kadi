<?php

namespace App\Support;

class PhoneNumber
{
    public static function sanitize(string $value): string
    {
        return preg_replace('/[^0-9]/', '', $value) ?? '';
    }

    public static function normalize(string $value): string
    {
        $digits = self::sanitize($value);

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '254'.substr($digits, 1);
        }

        if (str_starts_with($digits, '7') && strlen($digits) === 9) {
            return '254'.$digits;
        }

        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            return $digits;
        }

        return $digits;
    }
}
