<?php

declare(strict_types=1);

namespace Salon\Model;

use Salon\DB\Database;
use Salon\Http\TenantResolver;
use Salon\Support\Color;
use Salon\Support\Config;

/** Admin accounts, roles and the personal colour theme each admin picks. */
final class Admin
{
    public const ROLES = ['platform' => 'Platform', 'owner' => 'Owner', 'manager' => 'Manager', 'staff' => 'Staff'];

    /** Everything a role can be given. "tenants" is the platform-level ability. */
    public const ABILITIES = ['bookings', 'services', 'statuses', 'schedule', 'content', 'settings', 'sms',
        'admins', 'theme', 'activity', 'tenants'];

    private const ROLE_MATRIX = [
        'platform' => self::ABILITIES,
        'owner' => ['bookings', 'services', 'statuses', 'schedule', 'content', 'settings', 'sms',
            'admins', 'theme', 'activity'],
        'manager' => ['bookings', 'services', 'statuses', 'schedule', 'content', 'sms', 'theme', 'activity'],
        'staff' => ['bookings', 'theme'],
    ];

    /** The house palette: these are the colours the site already shipped with. */
    public const PRESETS = [
        'house'     => ['label' => 'House (default)', 'primary' => '#3a1233', 'accent' => '#d6336c', 'bg' => '#f6f1f4', 'surface' => '#ffffff', 'text' => '#2a1626'],
        'midnight'  => ['label' => 'Midnight',        'primary' => '#161a2b', 'accent' => '#4f6bed', 'bg' => '#f1f3f9', 'surface' => '#ffffff', 'text' => '#1b2033'],
        'forest'    => ['label' => 'Forest',          'primary' => '#12332a', 'accent' => '#1c7a4b', 'bg' => '#f1f6f2', 'surface' => '#ffffff', 'text' => '#16261f'],
        'sand'      => ['label' => 'Warm sand',       'primary' => '#3d2a17', 'accent' => '#c07b2a', 'bg' => '#f8f4ee', 'surface' => '#ffffff', 'text' => '#2b1f13'],
        'afterdark' => ['label' => 'After dark',      'primary' => '#120e16', 'accent' => '#e0a82e', 'bg' => '#1a1520', 'surface' => '#241d2c', 'text' => '#f3eef6'],
    ];

    public static function find(int $id): ?array
    {
        $tenantId = TenantResolver::getId();
        return Database::one('SELECT * FROM admins WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
    }

    public static function findByUsername(string $username): ?array
    {
        $tenantId = TenantResolver::getId();
        return Database::one('SELECT * FROM admins WHERE username = ? AND active = 1 AND tenant_id = ?', [$username, $tenantId]);
    }

    public static function all(): array
    {
        $tenantId = TenantResolver::getId();
        return Database::all('SELECT id, first_name, last_name, username, email, phone, role, active, sms_opt_in,
            theme_preset, theme_primary, theme_accent, theme_bg, theme_surface, theme_text, last_login_at
            FROM admins WHERE tenant_id = ? ORDER BY role, first_name', [$tenantId]);
    }

    /** Admins who asked to receive booking SMS. */
    public static function smsRecipients(): array
    {
        $tenantId = TenantResolver::getId();
        return Database::all('SELECT id, first_name, phone FROM admins WHERE active = 1 AND sms_opt_in = 1
            AND phone <> ? AND tenant_id = ? ORDER BY id', ['', $tenantId]);
    }

    public static function hash(string $plain): string
    {
        $algo = defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13') && in_array('argon2id', password_algos(), true)
            ? PASSWORD_ARGON2ID
            : PASSWORD_BCRYPT;
        return password_hash($plain, $algo);
    }

    public static function create(array $d): int
    {
        $tenantId = TenantResolver::getId();
        return Database::insert(
            'INSERT INTO admins (tenant_id, first_name, last_name, username, email, phone, pass_hash, role, active,
                sms_opt_in, force_password_change, theme_preset) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $tenantId,
                mb_substr(trim($d['first_name'] ?? ''), 0, 60),
                mb_substr(trim($d['last_name'] ?? ''), 0, 60),
                strtolower(mb_substr(trim($d['username'] ?? ''), 0, 60)),
                mb_substr(trim($d['email'] ?? ''), 0, 160),
                mb_substr(trim($d['phone'] ?? ''), 0, 24),
                self::hash($d['password'] ?? ''),
                in_array($d['role'] ?? 'staff', array_keys(self::ROLES), true) ? $d['role'] : 'staff',
                !empty($d['active']) ? 1 : 0,
                !empty($d['sms_opt_in']) ? 1 : 0,
                !empty($d['force_password_change']) ? 1 : 0,
                self::presetKey($d['theme_preset'] ?? 'house'),
            ]
        );
    }

    public static function update(int $id, array $d): void
    {
        Database::exec(
            'UPDATE admins SET first_name=?, last_name=?, email=?, phone=?, role=?, active=?, sms_opt_in=?,
             theme_preset=? WHERE id=?',
            [
                mb_substr(trim($d['first_name'] ?? ''), 0, 60),
                mb_substr(trim($d['last_name'] ?? ''), 0, 60),
                mb_substr(trim($d['email'] ?? ''), 0, 160),
                mb_substr(trim($d['phone'] ?? ''), 0, 24),
                in_array($d['role'] ?? 'staff', array_keys(self::ROLES), true) ? $d['role'] : 'staff',
                !empty($d['active']) ? 1 : 0,
                !empty($d['sms_opt_in']) ? 1 : 0,
                self::presetKey($d['theme_preset'] ?? 'house'),
                $id,
            ]
        );
    }

    public static function saveTheme(int $id, array $theme): void
    {
        Database::exec(
            'UPDATE admins SET theme_preset=?, theme_primary=?, theme_accent=?, theme_bg=?, theme_surface=?,
             theme_text=? WHERE id=?',
            [
                self::presetKey($theme['preset'] ?? 'custom'),
                Color::normalize($theme['primary'] ?? self::PRESETS['house']['primary'], '#3a1233'),
                Color::normalize($theme['accent'] ?? self::PRESETS['house']['accent'], '#d6336c'),
                Color::normalize($theme['bg'] ?? self::PRESETS['house']['bg'], '#f6f1f4'),
                Color::normalize($theme['surface'] ?? self::PRESETS['house']['surface'], '#ffffff'),
                Color::normalize($theme['text'] ?? self::PRESETS['house']['text'], '#2a1626'),
                $id,
            ]
        );
    }

    public static function setPassword(int $id, string $plain, bool $forceChange = false): void
    {
        Database::exec('UPDATE admins SET pass_hash = ?, force_password_change = ? WHERE id = ?',
            [self::hash($plain), $forceChange ? 1 : 0, $id]);
    }

    public static function touchLogin(int $id): void
    {
        Database::exec('UPDATE admins SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public static function delete(int $id): bool
    {
        if ((int) (Database::value('SELECT COUNT(*) FROM admins WHERE active = 1') ?? 1) <= 1) {
            return false;
        }
        return Database::exec('DELETE FROM admins WHERE id = ?', [$id]) > 0;
    }

    public static function presetKey(string $key): string
    {
        return isset(self::PRESETS[$key]) ? $key : 'custom';
    }

    /** Resolve the CSS variables for one admin; anything missing falls back to the house palette. */
    public static function theme(?array $admin): array
    {
        $preset = self::PRESETS['house'];
        if ($admin !== null && isset(self::PRESETS[$admin['theme_preset']])) {
            $preset = self::PRESETS[$admin['theme_preset']];
        }
        $pick = static fn(string $key, string $fromPreset): string => $admin !== null && $admin['theme_preset'] === 'custom'
            ? Color::normalize((string) ($admin['theme_' . $key] ?? $fromPreset), $fromPreset)
            : Color::normalize($fromPreset);
        $theme = [
            'primary' => $pick('primary', $preset['primary']),
            'accent'  => $pick('accent', $preset['accent']),
            'bg'      => $pick('bg', $preset['bg']),
            'surface' => $pick('surface', $preset['surface']),
            'text'    => $pick('text', $preset['text']),
        ];
        $theme['preset'] = $admin['theme_preset'] ?? 'house';
        $theme['accent_hover'] = Color::shift($theme['accent'], -0.18);
        $theme['primary_soft'] = Color::tint($theme['primary'], 0.92);
        $theme['accent_soft'] = Color::tint($theme['accent'], 0.86);
        $theme['line'] = Color::tint($theme['primary'], 0.82);
        $theme['muted'] = Color::shift($theme['text'], 0.38);
        $theme['on_primary'] = Color::foreground($theme['primary']);
        $theme['on_accent'] = Color::foreground($theme['accent']);
        $theme['gold'] = Color::normalize($preset['accent'] === '#d6336c' ? '#e0a82e' : $theme['accent'], '#e0a82e');
        $theme['is_dark'] = Color::foreground($theme['bg']) === '#ffffff';
        return $theme;
    }

    /** Accounts that can still manage the team, used to protect the last one. */
    public static function countOwners(): int
    {
        $tenantId = TenantResolver::getId();
        return (int) (Database::value(
            "SELECT COUNT(*) FROM admins WHERE role IN ('platform', 'owner') AND active = 1 AND tenant_id = ?",
            [$tenantId]
        ) ?? 0);
    }

    public static function canManage(?array $actor, string $ability = 'admins'): bool
    {
        if ($actor === null) {
            return false;
        }
        return in_array($ability, self::ROLE_MATRIX[$actor['role'] ?? 'staff'] ?? [], true);
    }
}
