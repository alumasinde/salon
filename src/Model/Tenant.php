<?php

declare(strict_types=1);

namespace Salon\Model;

use Salon\DB\Database;
use Salon\Support\Config;
use RuntimeException;

/** Tenant management for multi-tenant support. */
final class Tenant
{
    private static ?array $current = null;

    public static function current(): ?array
    {
        if (self::$current !== null) {
            return self::$current;
        }
        return null;
    }

    public static function setCurrent(array $tenant): void
    {
        self::$current = $tenant;
    }

    public static function findById(int $id): ?array
    {
        return Database::one('SELECT * FROM tenants WHERE id = ?', [$id]);
    }

    public static function findByDomain(string $domain): ?array
    {
        $domain = strtolower(trim($domain));
        return Database::one('SELECT * FROM tenants WHERE domain = ? AND status = ?', [$domain, 'active']);
    }

    /** Domain owner regardless of status — used to keep domains unique. */
    public static function findByDomainAny(string $domain): ?array
    {
        $domain = strtolower(trim($domain));
        return Database::one('SELECT * FROM tenants WHERE domain = ?', [$domain]);
    }

    public static function findBySlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        return Database::one('SELECT * FROM tenants WHERE slug = ?', [$slug]);
    }

    /** The tenant used when the host cannot identify one: command line and local development. */
    public static function defaultTenant(): ?array
    {
        $wanted = strtolower(trim(Config::get('DEFAULT_TENANT', '')));
        if ($wanted !== '') {
            return self::findByDomainAny($wanted) ?? self::findBySlug($wanted);
        }
        return Database::one('SELECT * FROM tenants WHERE status = ? ORDER BY id LIMIT 1', ['active']);
    }

    public static function all(): array
    {
        return Database::all('SELECT * FROM tenants ORDER BY name');
    }

    public static function active(): array
    {
        return Database::all('SELECT * FROM tenants WHERE status = ? ORDER BY name', ['active']);
    }

    public static function create(array $data): int
    {
        $slug = self::generateSlug($data['name'] ?? '');
        if (self::findBySlug($slug) !== null) {
            throw new RuntimeException('A tenant with this name already exists.');
        }
        if (self::findByDomainAny($data['domain'] ?? '') !== null) {
            throw new RuntimeException('This domain is already registered.');
        }

        return Database::insert(
            'INSERT INTO tenants (domain, name, slug, status, plan, max_admins, max_bookings_per_month,
                primary_color, accent_color, logo_url, favicon_url, custom_css) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                strtolower(trim($data['domain'] ?? '')),
                trim($data['name'] ?? ''),
                $slug,
                $data['status'] ?? 'pending',
                $data['plan'] ?? 'basic',
                (int) ($data['max_admins'] ?? 5),
                (int) ($data['max_bookings_per_month'] ?? 500),
                self::normalizeColor($data['primary_color'] ?? '#3a1233'),
                self::normalizeColor($data['accent_color'] ?? '#d6336c'),
                trim((string) ($data['logo_url'] ?? '')),
                trim((string) ($data['favicon_url'] ?? '')),
                (string) ($data['custom_css'] ?? ''),
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        $tenant = self::findById($id);
        if ($tenant === null) {
            throw new RuntimeException('Tenant not found.');
        }

        $updates = [];
        $params = [];

        if (isset($data['name'])) {
            $slug = self::generateSlug($data['name']);
            $existing = self::findBySlug($slug);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new RuntimeException('A tenant with this name already exists.');
            }
            $updates[] = 'name = ?';
            $updates[] = 'slug = ?';
            $params[] = trim($data['name']);
            $params[] = $slug;
        }

        if (isset($data['domain'])) {
            $existing = self::findByDomainAny($data['domain']);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new RuntimeException('This domain is already registered.');
            }
            $updates[] = 'domain = ?';
            $params[] = strtolower(trim($data['domain']));
        }

        if (isset($data['status'])) {
            $updates[] = 'status = ?';
            $params[] = $data['status'];
        }

        if (isset($data['plan'])) {
            $updates[] = 'plan = ?';
            $params[] = $data['plan'];
        }

        if (isset($data['max_admins'])) {
            $updates[] = 'max_admins = ?';
            $params[] = (int) $data['max_admins'];
        }

        if (isset($data['max_bookings_per_month'])) {
            $updates[] = 'max_bookings_per_month = ?';
            $params[] = (int) $data['max_bookings_per_month'];
        }

        if (isset($data['primary_color'])) {
            $updates[] = 'primary_color = ?';
            $params[] = self::normalizeColor($data['primary_color']);
        }

        if (isset($data['accent_color'])) {
            $updates[] = 'accent_color = ?';
            $params[] = self::normalizeColor($data['accent_color']);
        }

        if (isset($data['logo_url'])) {
            $updates[] = 'logo_url = ?';
            $params[] = trim($data['logo_url']);
        }

        if (isset($data['favicon_url'])) {
            $updates[] = 'favicon_url = ?';
            $params[] = trim($data['favicon_url']);
        }

        if (isset($data['custom_css'])) {
            $updates[] = 'custom_css = ?';
            $params[] = $data['custom_css'];
        }

        if ($updates === []) {
            return;
        }

        $params[] = $id;
        Database::exec('UPDATE tenants SET ' . implode(', ', $updates) . ' WHERE id = ?', $params);
    }

    public static function delete(int $id): bool
    {
        $tenant = self::findById($id);
        if ($tenant === null) {
            return false;
        }

        // Check if tenant has data
        $hasBookings = (int) (Database::value('SELECT COUNT(*) FROM bookings WHERE tenant_id = ?', [$id]) ?? 0) > 0;
        $hasAdmins = (int) (Database::value('SELECT COUNT(*) FROM admins WHERE tenant_id = ?', [$id]) ?? 0) > 0;

        if ($hasBookings || $hasAdmins) {
            throw new RuntimeException('Cannot delete tenant with existing data. Archive it instead.');
        }

        return Database::exec('DELETE FROM tenants WHERE id = ?', [$id]) > 0;
    }

    public static function countAdmins(int $tenantId): int
    {
        return (int) (Database::value('SELECT COUNT(*) FROM admins WHERE tenant_id = ? AND active = 1', [$tenantId]) ?? 0);
    }

    public static function countBookingsThisMonth(int $tenantId): int
    {
        return (int) (Database::value(
            'SELECT COUNT(*) FROM bookings WHERE tenant_id = ? AND booking_date >= DATE_FORMAT(NOW(), "%Y-%m-01")',
            [$tenantId]
        ) ?? 0);
    }

    public static function canAddAdmin(int $tenantId): bool
    {
        $tenant = self::findById($tenantId);
        if ($tenant === null) {
            return false;
        }
        return self::countAdmins($tenantId) < (int) $tenant['max_admins'];
    }

    public static function canAddBooking(int $tenantId): bool
    {
        $tenant = self::findById($tenantId);
        if ($tenant === null) {
            return false;
        }
        return self::countBookingsThisMonth($tenantId) < (int) $tenant['max_bookings_per_month'];
    }

    private static function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = mb_substr($slug, 0, 60);
        return $slug ?: 'tenant';
    }

    private static function normalizeColor(string $color): string
    {
        $color = trim($color);
        if (!preg_match('/^#[0-9a-f]{6}$/i', $color)) {
            return '#3a1233';
        }
        return strtolower($color);
    }

    public static function getTheme(?array $tenant): array
    {
        if ($tenant === null) {
            return [
                'primary' => '#3a1233',
                'accent' => '#d6336c',
                'primary_soft' => '#f6f1f4',
                'accent_soft' => '#fce4ec',
            ];
        }

        return [
            'primary' => $tenant['primary_color'] ?? '#3a1233',
            'accent' => $tenant['accent_color'] ?? '#d6336c',
            'primary_soft' => self::lightenColor($tenant['primary_color'] ?? '#3a1233', 0.92),
            'accent_soft' => self::lightenColor($tenant['accent_color'] ?? '#d6336c', 0.86),
        ];
    }

    private static function lightenColor(string $hex, float $factor): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = (int) ($r + (255 - $r) * $factor);
        $g = (int) ($g + (255 - $g) * $factor);
        $b = (int) ($b + (255 - $b) * $factor);

        return sprintf('#%02x%02x%02x', min(255, $r), min(255, $g), min(255, $b));
    }
}
