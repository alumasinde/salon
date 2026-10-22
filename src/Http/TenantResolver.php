<?php

declare(strict_types=1);

namespace Salon\Http;

use Salon\Model\Tenant;
use Salon\Support\Config;

/** Resolves the current tenant from the request hostname. */
final class TenantResolver
{
    private static ?array $tenant = null;
    private static bool $resolved = false;

    public static function resolve(): ?array
    {
        if (self::$resolved) {
            return self::$tenant;
        }

        self::$resolved = true;

        $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));

        // Remove port if present
        $host = (string) preg_replace('/:\d+$/', '', $host);

        // Check for tenant by domain
        $tenant = Tenant::findByDomain($host);

        if ($tenant === null && !self::isPlatformDomain($host) && self::mayUseDefault($host)) {
            // Command line tools have no host at all, and a development machine reaches
            // the site by IP or as "localhost", so neither can match a registered domain.
            $tenant = Tenant::defaultTenant();
        }

        self::$tenant = $tenant;
        return $tenant;
    }

    /** The platform domain hosts no salon; its landing page is a separate concern. */
    private static function isPlatformDomain(string $host): bool
    {
        $platform = strtolower(trim(Config::get('PLATFORM_DOMAIN', '')));
        return $platform !== '' && $host === $platform;
    }

    private static function mayUseDefault(string $host): bool
    {
        return PHP_SAPI === 'cli' || $host === '' || $host === 'localhost'
            || filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    public static function current(): ?array
    {
        if (!self::$resolved) {
            self::resolve();
        }
        return self::$tenant;
    }

    public static function require(): array
    {
        $tenant = self::current();
        if ($tenant === null) {
            http_response_code(404);
            $detail = Config::debug()
                ? ' No active tenant is registered for host "' . e((string) ($_SERVER['HTTP_HOST'] ?? '')) . '".'
                : '';
            exit('<!DOCTYPE html><html><head><title>Site not found</title></head>'
                . '<body style="font:16px system-ui;max-width:640px;margin:60px auto;padding:0 20px">'
                . '<h1>Site not found</h1><p>This site does not exist or has been suspended.' . $detail . '</p></body></html>');
        }
        return $tenant;
    }

    public static function getId(): int
    {
        $tenant = self::require();
        return (int) $tenant['id'];
    }

    /** For rows that must still be written when no tenant can be resolved, such as logs. */
    public static function idOrNull(): ?int
    {
        $tenant = self::current();
        return $tenant === null ? null : (int) $tenant['id'];
    }

    public static function isActive(): bool
    {
        $tenant = self::current();
        return $tenant !== null && $tenant['status'] === 'active';
    }
}
