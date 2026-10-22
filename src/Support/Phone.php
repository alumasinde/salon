<?php

declare(strict_types=1);

namespace Salon\Support;

/** Kenyan phone handling: validation, storage and display formats. */
final class Phone
{
    /** Accepts 07xx, 01xx, +2547xx, 2547xx. Returns 2547XXXXXXXX or null. */
    public static function normalize(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if (strlen($digits) < 9) {
            return null;
        }
        if (str_starts_with($digits, '00254')) {
            $digits = substr($digits, 5);
        }
        if (str_starts_with($digits, '0')) {
            $digits = '254' . substr($digits, 1);
        } elseif (!str_starts_with($digits, '254')) {
            $digits = '254' . $digits;
        }
        return preg_match('/^254[17]\d{8}$/', $digits) ? $digits : null;
    }

    public static function isKenyan(string $raw): bool
    {
        return self::normalize($raw) !== null;
    }

    /** Human-friendly local form, e.g. 0712 345 678. */
    public static function display(string $stored): string
    {
        $local = preg_replace('/^254/', '0', $stored) ?? $stored;
        return strlen($local) === 10
            ? substr($local, 0, 4) . ' ' . substr($local, 4, 3) . ' ' . substr($local, 7)
            : $local;
    }
}
