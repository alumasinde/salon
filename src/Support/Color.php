<?php

declare(strict_types=1);

namespace Salon\Support;

/** Hex colour maths used to derive theme shades. */
final class Color
{
    public static function isHex(string $value): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $value);
    }

    public static function normalize(string $value, string $fallback = '#000000'): string
    {
        $value = trim($value);
        if (preg_match('/^#[0-9a-fA-F]{3}$/', $value)) {
            $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
        }
        return self::isHex($value) ? strtolower($value) : $fallback;
    }

    /** @param float $amount -1 (black) .. 1 (white) */
    public static function shift(string $hex, float $amount): string
    {
        $rgb = self::rgb($hex);
        $target = $amount >= 0 ? 255 : 0;
        $out = [];
        foreach ($rgb as $channel) {
            $mixed = (int) round($channel + ($target - $channel) * abs($amount));
            $out[] = max(0, min(255, $mixed));
        }
        return sprintf('#%02x%02x%02x', ...$out);
    }

    /** Perceived luminance based foreground: white or near-black ink. */
    public static function foreground(string $hex): string
    {
        [$r, $g, $b] = self::rgb($hex);
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#1c1219' : '#ffffff';
    }

    /** Same hue at low saturation, for backgrounds derived from a brand colour. */
    public static function tint(string $hex, float $amount = 0.9): string
    {
        return self::shift($hex, $amount);
    }

    private static function rgb(string $hex): array
    {
        $hex = self::normalize($hex);
        return [
            (int) hexdec(substr($hex, 1, 2)),
            (int) hexdec(substr($hex, 3, 2)),
            (int) hexdec(substr($hex, 5, 2)),
        ];
    }
}
