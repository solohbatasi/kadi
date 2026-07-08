<?php

namespace App\Support;

class Mask
{
    public static function phone(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $digits = preg_replace('/[^0-9]/', '', $value) ?? '';

        if (strlen($digits) <= 6) {
            return str_repeat('*', strlen($digits));
        }

        return substr($digits, 0, 4).'****'.substr($digits, -3);
    }

    public static function apiKey(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (strlen($value) <= 12) {
            return substr($value, 0, 4).'****';
        }

        return substr($value, 0, 10).'****'.substr($value, -4);
    }

    public static function secret(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return '[redacted:'.strlen($value).']';
    }

    public static function arraySensitive(array $payload): array
    {
        $sensitive = ['secret', 'token', 'passkey', 'password', 'credential', 'authorization', 'api_key', 'apikey', 'consumer_secret'];

        array_walk_recursive($payload, function (&$value, $key) use ($sensitive): void {
            $key = strtolower((string) $key);

            if (is_string($value) && str_contains($key, 'phone')) {
                $value = self::phone($value);
                return;
            }

            foreach ($sensitive as $needle) {
                if (str_contains($key, $needle)) {
                    $value = is_scalar($value) ? self::secret((string) $value) : '[redacted]';
                    return;
                }
            }
        });

        return $payload;
    }
}

