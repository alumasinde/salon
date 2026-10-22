<?php

declare(strict_types=1);

namespace Salon\Support;

/** Environment-backed configuration with typed access. */
final class Config
{
    public static function get(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return (string) $value;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, (string) $default);
        return $value === '' ? $default : (int) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = strtolower(self::get($key, $default ? 'true' : 'false'));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function debug(): bool
    {
        return self::bool('APP_DEBUG');
    }

    /** Application secret used for CSRF/session binding. Falls back to a per-install random value. */
    public static function key(): string
    {
        $key = self::get('APP_KEY');
        if (strlen($key) >= 32) {
            return $key;
        }
        $file = SALON_ROOT . '/storage/.appkey';
        if (!is_readable($file)) {
            @mkdir(dirname($file), 0775, true);
            file_put_contents($file, bin2hex(random_bytes(32)));
        }
        return trim((string) file_get_contents($file));
    }
}
