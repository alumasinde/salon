<?php

declare(strict_types=1);

namespace Salon\Model;

use Salon\DB\Database;
use Salon\Http\TenantResolver;

/** Site-wide key/value configuration, cached per request. */
final class Setting
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            $tenantId = TenantResolver::getId();
            self::$cache = array_column(
                Database::all('SELECT `key`, `value` FROM settings WHERE tenant_id = ?', [$tenantId]),
                'value',
                'key'
            );
        }
        return self::$cache;
    }

    public static function rows(): array
    {
        $tenantId = TenantResolver::getId();
        return Database::all('SELECT `key`, `value`, `label` FROM settings WHERE tenant_id = ? ORDER BY `key`', [$tenantId]);
    }

    public static function get(string $key, string $default = ''): string
    {
        $value = self::all()[$key] ?? null;
        return $value === null || $value === '' ? $default : $value;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, (string) $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = strtolower(self::get($key, $default ? '1' : '0'));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function set(string $key, string $value, string $label = ''): void
    {
        $tenantId = TenantResolver::getId();
        Database::exec(
            'INSERT INTO settings (`key`, `value`, `label`, tenant_id) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, $value, $label, $tenantId]
        );
        self::$cache = null;
    }

    /** Write only keys that already exist in the settings table. */
    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            if (is_string($key)) {
                self::set($key, is_bool($value) ? ($value ? '1' : '0') : trim((string) $value));
            }
        }
    }

    public static function createIfMissing(string $key, string $value, string $label): void
    {
        $tenantId = TenantResolver::getId();
        Database::exec('INSERT IGNORE INTO settings (`key`, `value`, `label`, tenant_id) VALUES (?,?,?,?)', [$key, $value, $label, $tenantId]);
        self::$cache = null;
    }
}
