<?php

declare(strict_types=1);

namespace Salon\Http;

use Salon\Support\Config;

/** Session-bound CSRF token for the admin panel. */
final class Csrf
{
    public static function token(): string
    {
        Auth::start();
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    }

    public static function check(?string $value): bool
    {
        Auth::start();
        $expected = $_SESSION['csrf'] ?? '';
        return is_string($value) && $value !== '' && $expected !== '' && hash_equals($expected, $value);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf" value="' . e(self::token()) . '">';
    }

    /**
     * Stateless single-purpose token for public forms: base64 payload + HMAC.
     * Avoids a session cookie on the marketing pages.
     */
    public static function issue(string $purpose, int $ttl = 86400): string
    {
        $payload = base64_encode(json_encode([$purpose, time() + $ttl]));
        return $payload . '.' . hash_hmac('sha256', $payload, Config::key());
    }

    public static function verifyToken(string $value, string $purpose): bool
    {
        [$payload, $signature] = array_pad(explode('.', $value, 2), 2, '');
        if ($payload === '' || $signature === '') {
            return false;
        }
        if (!hash_equals(hash_hmac('sha256', $payload, Config::key()), $signature)) {
            return false;
        }
        $decoded = json_decode((string) base64_decode($payload, true), true);
        return is_array($decoded) && ($decoded[0] ?? '') === $purpose && (int) ($decoded[1] ?? 0) >= time();
    }
}
